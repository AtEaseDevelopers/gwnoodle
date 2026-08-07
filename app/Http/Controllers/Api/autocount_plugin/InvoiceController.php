<?php

namespace App\Http\Controllers\Api\autocount_plugin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\ApiLog;

class InvoiceController extends Controller
{

    const is_testing   = true; // testing with 5, adjust as needed
    const cut_off_date = '2026-04-07 00:00:00'; // only sync invoices created after this date (for testing)
    /**
     * Get all pending invoices for AutoCount sync
     */
    public function syncPending(Request $request)
    {
        try {
            // Step 1: Get invoices (without customers join)
            $invoices = Invoice::query()
                ->leftJoin('drivers',      'invoices.driver_id',      '=', 'drivers.id')
                ->leftJoin('kelindans',    'invoices.kelindan_id',    '=', 'kelindans.id')
                ->leftJoin('agents',       'invoices.agent_id',       '=', 'agents.id')
                ->leftJoin('supervisors',  'invoices.supervisor_id',  '=', 'supervisors.id')
                ->where('invoices.status', 1)
                ->where('invoices.autocount_status', 'pending')
                // Check if the cut_off_date is NOT empty
                ->when(!empty(self::cut_off_date), function ($query) {
                    $query->where('invoices.created_at', '>=', self::cut_off_date);
                })
                ->select([
                    'invoices.*',
                    'drivers.employeeid     as driver_employeeid',
                    'kelindans.employeeid   as kelindan_employeeid',
                    'agents.employeeid      as agent_employeeid',
                    'supervisors.employeeid as supervisor_employeeid',
                ])
                ->get();

            // Step 2: Customers (separate - optimized)
            $customerIds = $invoices->pluck('customer_id')->filter()->unique();
            $customers = Customer::whereIn('id', $customerIds)->pluck('code', 'id');

            // Step 3: Get ALL invoice details (batch query)
            $invoiceIds = $invoices->pluck('id');
            $details = \DB::table('invoice_details')
                ->leftJoin('products', 'invoice_details.product_id', '=', 'products.id')
                ->whereIn('invoice_details.invoice_id', $invoiceIds)
                ->select([
                    'invoice_details.*',
                    'products.name as product_name',
                    'products.unit_code',
                    'products.classification_code',
                    'products.cost as cost',
                ])->get()->groupBy('invoice_id'); // 🔥 important

            // Step 4: Payment mapping
            $paymentMap = [
                1 => 'Cash',
                2 => 'Credit',
                3 => 'Online',
                4 => 'Touch & Go',
                5 => 'Cheque',
            ];

            // Step 5: Transform
            $data = $invoices->map(function ($inv) use ($customers, $paymentMap, $details) {
                return [
                    'id'                    => $inv->id,
                    'invoice_no'            => $inv->invoiceno,
                    'date'                  => $inv->date,
                    'customer_id'           => $inv->customer_id,
                    'customer_code'         => $customers[$inv->customer_id] ?? null,
                    'driver_employeeid'     => $inv->driver_employeeid,
                    'kelindan_employeeid'   => $inv->kelindan_employeeid,
                    'agent_employeeid'      => $inv->agent_employeeid,
                    'supervisor_employeeid' => $inv->supervisor_employeeid,
                    'payment_term'          => $paymentMap[$inv->paymentterm] ?? 'Unknown',
                    'chequeno'              => $inv->chequeno,
                    'payment_proof'         => $inv->paymentproof,
                    'remark'                => $inv->remark . (self::is_testing ? ' [TESTING]' : ''),
                    'is_testing'            => self::is_testing,
                    'details' => isset($details[$inv->id])
                        ? $details[$inv->id]->map(function ($d) {
                            return [
                                'id'                  => $d->id,
                                'invoice_id'          => $d->invoice_id,
                                'product_id'          => $d->product_id,
                                'quantity'            => $d->quantity,
                                // Send unit price derived from the authoritative line total so
                                // AutoCount's Qty x UnitPrice equals VMS totalprice. The stored
                                // `price` column is double(10,3) and rounds fractional-sen prices
                                // (e.g. 0.025 -> 0.02), which would undercharge AutoCount.
                                'price'               => $d->quantity > 0
                                    ? round($d->totalprice / $d->quantity, 4)
                                    : $d->price,
                                'cost'                => $d->cost,
                                'remark'              => $d->remark,
                                'product_name'        => $d->product_name,
                                'unit_code'           => $d->unit_code,
                                'classification_code' => $d->classification_code,
                            ];
                        })
                        : [],
                ];
            });

            // Prepare Success Response
            $responseData = [
                'status' => 'success',
                'count'  => $data->count(),
                'data'   => $data
            ];
            $statusCode = 200;

        } catch (\Exception $e) {
            // Prepare Error Response
            $responseData = [
                'status'  => 'error',
                'message' => $e->getMessage(),
                'line'    => $e->getLine()
            ];
            $statusCode = 500;
        }

        return response()->json($responseData, $statusCode);
    }

    /**
     * Update invoice autocount status from webhook
     */
    public function update(Request $request)
    {
        try {
            // 2. Use Validator::make instead of $request->validate() so we can log validation errors
            $validator = Validator::make($request->all(), [
                'invoice_no'       => 'required',
                'autocount_status' => 'required'
            ]);

            if ($validator->fails()) {
                $responseData = [
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors()
                ];
                $statusCode = 422; // Unprocessable Entity
            } else {
                // Find Invoice
                $invoice = Invoice::find($request->id);
                if (!$invoice) {
                    $responseData = [
                        'status'  => 'error',
                        'message' => 'Invoice not found'
                    ];
                    $statusCode = 404; // Not Found
                } else {
                    $incomingStatus = $request->autocount_status;

                    // If the sync failed and this invoice has not been
                    // auto-requeued before, flip it back to 'pending' so it
                    // gets picked up again. This only happens once - a second
                    // failure is left as 'failed'.
                    if (strtolower($incomingStatus) === 'failed' && !$invoice->autocount_auto_retried) {
                        $invoice->autocount_status       = 'pending';
                        $invoice->autocount_auto_retried = true;
                    } else {
                        $invoice->autocount_status = $incomingStatus;
                    }

                    $invoice->autocount_message = $request->all() ?? null; // Optional message field
                    $invoice->save();

                    $responseData = [
                        'status' => 'ok'
                    ];
                    $statusCode = 200; // Success
                }
            }

        } catch (\Exception $e) {
            $responseData = [
                'status'  => 'error',
                'message' => $e->getMessage(),
                'line'    => $e->getLine()
            ];
            $statusCode = 500; // Server Error
        }

        return response()->json($responseData, $statusCode);
    }

    public function updateLog(Request $request)
    {
       // ApiLog::createLog($request);
    }
}