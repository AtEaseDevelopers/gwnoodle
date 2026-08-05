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
    // payments are never retro-pushed. AR Payment sync went live on 05 Aug 2026.
    // Empty string disables the cut-off.
    const cut_off_date = '2026-08-05 00:00:00';

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

            // Step 3: group rows by batch and emit one AR Payment per batch.
            $data = $rows->groupBy('payment_batch_id')
                ->filter(function ($batch) {
                    // Eligibility: keep the batch only if EVERY invoice in it is
                    // already synced to AutoCount (has a doc number). An OR cannot
                    // knock off an invoice that has not been created yet.
                    return $batch->every(function ($r) {
                        return !empty($r->invoice_no)
                            && $r->invoice_autocount_status === 'success';
                    });
                })
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

            foreach ($rows as $row) {
                // On the FIRST failure auto-requeue the batch once; a second failure
                // is left as 'failed' for manual handling.
                if ($incomingStatus === InvoicePayment::AC_FAILED && !$row->autocount_auto_retried) {
                    $row->autocount_status       = InvoicePayment::AC_PENDING;
                    $row->autocount_auto_retried = true;
                } else {
                    $row->autocount_status = $incomingStatus;
                }

                // Store AutoCount's OR identifiers so a resync edits (not duplicates).
                if ($request->filled('payment_no')) {
                    $row->payment_no = $request->payment_no;
                }
                if ($request->filled('doc_id')) {
                    $row->doc_id = $request->doc_id;
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
