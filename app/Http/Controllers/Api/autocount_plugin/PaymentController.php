<?php

namespace App\Http\Controllers\Api\autocount_plugin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Customer;

/**
 * AutoCount AR Payment (Official Receipt) sync.
 *
 * A "payment event" (one cheque / one deposit) is stored as N invoice_payment
 * rows sharing one payment_batch_id. This controller groups them back into a
 * single batch so the plugin creates ONE AR Payment that knocks off all the
 * invoices in the batch.
 *
 * Routing (decided by the INVOICE's payment term, not the payment row type):
 *   - non-credit invoices  -> auto sync   (rows created with autocount_status = 'pending')
 *   - any credit invoice   -> manual sync (rows created with autocount_status = 'hold',
 *                              released to 'pending' by a web "Sync" click)
 */
class PaymentController extends Controller
{
    // Only sync invoice-payments created on/after this date so historical
    // payments are never retro-pushed. Empty string disables the cut-off.
    const cut_off_date = '2026-08-04 00:00:00';

    // Max eligible batches returned per poll. Mirrors the plugin's per-cycle
    // cap so the web never hands over more than the plugin will process; the
    // remainder is re-served (oldest first) on the next poll.
    const MAX_BATCHES_PER_POLL = 20;

    // A batch stays pending while it waits for its invoice to be approved in
    // AutoCount. If that never happens it would retry forever, so we give up
    // and mark the batch 'failed' after this many hours. A human can still
    // re-queue it later with a "Sync" click. 0 disables the timeout.
    // Runtime override: .env AUTOCOUNT_PAYMENT_WAIT_GIVEUP_HOURS
    const WAIT_GIVE_UP_HOURS = 72;

    // invoice_payments.type -> a human payment-method label. The plugin maps this
    // label to the company's AutoCount PaymentMethod code (configurable there).
    const PAYMENT_METHOD_MAP = [
        1 => 'Cash',
        2 => 'Credit',
        3 => 'Online',
        4 => 'Touch & Go',
        5 => 'Cheque',
    ];

    /**
     * Return one entry per eligible payment batch for AutoCount AR Payment sync.
     */
    public function syncPending(Request $request)
    {
        try {
            // Step 1: approved, queued payment rows joined to their invoice.
            $rows = InvoicePayment::query()
                ->join('invoices', 'invoice_payments.invoice_id', '=', 'invoices.id')
                ->whereNull('invoice_payments.deleted_at')
                ->whereNotNull('invoice_payments.payment_batch_id')
                ->where('invoice_payments.status', 1)                 // approved
                ->where('invoice_payments.autocount_status', InvoicePayment::AC_PENDING)
                ->when(!empty(self::cut_off_date), function ($q) {
                    $q->where('invoice_payments.created_at', '>=', self::cut_off_date);
                })
                ->orderBy('invoice_payments.created_at')   // oldest first, drains fairly
                ->select([
                    'invoice_payments.id',
                    'invoice_payments.payment_batch_id',
                    'invoice_payments.customer_id',
                    'invoice_payments.type',
                    'invoice_payments.amount',
                    'invoice_payments.chequeno',
                    'invoice_payments.remark',
                    'invoice_payments.payment_no',
                    'invoice_payments.doc_id',
                    'invoice_payments.created_at',
                    'invoices.invoiceno              as invoice_no',
                    'invoices.autocount_status       as invoice_autocount_status',
                ])
                ->get();

            // Step 2: customer codes (batch query).
            $customerCodes = Customer::whereIn('id', $rows->pluck('customer_id')->filter()->unique())
                ->pluck('code', 'id');

            // Step 3: group rows by batch.
            $grouped = $rows->groupBy('payment_batch_id');

            // Give-up sweep: a batch that has stayed pending past the timeout
            // (its invoice was never approved in AutoCount) is marked 'failed'
            // instead of being retried forever. It can still be re-queued by a
            // manual "Sync" click. Skipped when the window is 0.
            $giveUpHours = (int) env('AUTOCOUNT_PAYMENT_WAIT_GIVEUP_HOURS', self::WAIT_GIVE_UP_HOURS);
            if ($giveUpHours > 0) {
                $deadline = now()->subHours($giveUpHours);
                $timedOut = $grouped->filter(function ($batch) use ($deadline) {
                    $createdAt = optional($batch->first())->created_at;
                    return $createdAt !== null && $createdAt->lt($deadline);
                });
                foreach ($timedOut as $batchId => $batch) {
                    InvoicePayment::where('payment_batch_id', $batchId)
                        ->where('status', 1)
                        ->where('autocount_status', InvoicePayment::AC_PENDING)
                        ->update([
                            'autocount_status'  => InvoicePayment::AC_FAILED,
                            'autocount_message' => 'FIX: approve the invoice in AutoCount then click Sync | WHERE: web give-up sweep | WHY: batch stayed pending ' . $giveUpHours . 'h without its invoice being approved | BATCH: ' . $batchId,
                        ]);
                }
                $grouped = $grouped->diffKeys($timedOut);
            }

            // Step 4: emit one AR Payment per eligible batch.
            $data = $grouped
                ->filter(function ($batch) {
                    // Eligibility: keep the batch only if EVERY invoice in it is
                    // already synced to AutoCount (has a doc number). An OR cannot
                    // knock off an invoice that has not been created yet.
                    return $batch->every(function ($r) {
                        return !empty($r->invoice_no)
                            && $r->invoice_autocount_status === 'success';
                    });
                })
                // Cap per poll; the rest re-serves next cycle (oldest first).
                ->take(self::MAX_BATCHES_PER_POLL)
                ->map(function ($batch) use ($customerCodes) {
                    $first = $batch->first();

                    // payment_no / doc_id are identical across the batch once synced;
                    // present them so the plugin edits the existing OR (resync).
                    $existing = $batch->firstWhere('payment_no', '!=', null);

                    return [
                        'batch_id'       => $first->payment_batch_id,
                        'payment_ids'    => $batch->pluck('id')->values(),
                        'customer_id'    => $first->customer_id,
                        'customer_code'  => $customerCodes[$first->customer_id] ?? null,
                        'date'           => optional($first->created_at)->format('d-m-Y'),
                        'type'           => (int) $first->type,
                        'payment_method' => self::PAYMENT_METHOD_MAP[$first->type] ?? 'Unknown',
                        'cheque_no'      => $first->chequeno,
                        'remark'         => $first->remark,
                        // Present for resync (edit existing OR) - null on first sync.
                        'payment_no'     => optional($existing)->payment_no,
                        'doc_id'         => optional($existing)->doc_id,
                        'total'          => round($batch->sum('amount'), 2),
                        'knockoffs'      => $batch->map(function ($r) {
                            return [
                                'invoice_no' => $r->invoice_no,
                                'amount'     => round($r->amount, 2),
                            ];
                        })->values(),
                    ];
                })
                ->values();

            $responseData = [
                'status' => 'success',
                'count'  => $data->count(),
                'data'   => $data,
            ];
            $statusCode = 200;

        } catch (\Exception $e) {
            $responseData = [
                'status'  => 'error',
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ];
            $statusCode = 500;
        }

        return response()->json($responseData, $statusCode);
    }

    /**
     * True when a failure message means the invoice already has nothing
     * outstanding in AutoCount (so the payment is effectively recorded and the
     * "failure" is not a real error). Shared with the reconcile command so both
     * classify historical and live rows the same way.
     */
    public static function messageMeansAlreadyPaid(?string $message): bool
    {
        if ($message === null || $message === '') {
            return false;
        }

        foreach (['already fully paid', 'nothing outstanding', 'outstanding is 0'] as $needle) {
            if (stripos($message, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update the AutoCount status of every payment row in a batch.
     */
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'batch_id'         => 'required|string',
                'autocount_status' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $rows = InvoicePayment::where('payment_batch_id', $request->batch_id)->get();
            if ($rows->isEmpty()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Payment batch not found',
                ], 404);
            }

            $incomingStatus = strtolower($request->autocount_status);

            // "Already fully paid / nothing outstanding" is NOT a real sync
            // error: the invoice already has zero outstanding in AutoCount, so
            // there is nothing to knock off. Requeuing it only creates a
            // duplicate OR, so treat it as terminal - success if an OR was
            // actually issued, otherwise skipped (drops off the failed badge).
            $alreadyPaid = $incomingStatus === InvoicePayment::AC_FAILED
                && self::messageMeansAlreadyPaid((string) $request->input('autocount_message'));

            foreach ($rows as $row) {
                $hasOr = $request->filled('payment_no') || !empty($row->payment_no);

                if ($alreadyPaid) {
                    $row->autocount_status = $hasOr ? InvoicePayment::AC_SUCCESS : InvoicePayment::AC_SKIPPED;
                } elseif ($incomingStatus === InvoicePayment::AC_FAILED && !$row->autocount_auto_retried) {
                    // On the FIRST genuine failure auto-requeue the batch once;
                    // a second failure is left as 'failed' for manual handling.
                    $row->autocount_status       = InvoicePayment::AC_PENDING;
                    $row->autocount_auto_retried = true;
                } else {
                    $row->autocount_status = $incomingStatus;
                }

                // Store AutoCount's OR identifiers so a resync edits (not duplicates).
                if ($request->filled('payment_no')) {
                    $row->payment_no = $request->payment_no;
                }
                // Only persist a real DocKey. The plugin sends 0 when it has no
                // DocKey; saving that would wipe a previously stored key and
                // force the next resync to create a duplicate OR.
                if ($request->filled('doc_id') && (int) $request->doc_id > 0) {
                    $row->doc_id = (int) $request->doc_id;
                }

                $row->autocount_message = json_encode($request->all());
                $row->save();
            }

            return response()->json(['status' => 'ok'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }
}
