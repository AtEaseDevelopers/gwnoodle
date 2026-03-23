<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Customer;

class InvoiceController extends Controller
{

    /**
     * Get all pending invoices for AutoCount sync
     */
    public function syncPending()
    {
        // Step 1: Get invoices (without customers join)
        $invoices = Invoice::query()
            ->leftJoin('drivers',    'invoices.driver_id',     '=', 'drivers.id')
            ->leftJoin('kelindans',  'invoices.kelindan_id',   '=', 'kelindans.id')
            ->leftJoin('agents',     'invoices.agent_id',      '=', 'agents.id')
            ->leftJoin('supervisors','invoices.supervisor_id', '=', 'supervisors.id')

            ->where('invoices.status', 1)
            ->where('invoices.autocount_status', 'pending')

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
            ])->get()->groupBy('invoice_id'); // 🔥 important

        // Step 4: Payment mapping
        $paymentMap = [
            1 => 'Cash',
            2 => 'Bankin',
            3 => 'Credit Note',
            4 => 'Touch & Go',
        ];

        // Step 5: Transform
        $data = $invoices->map(function ($inv) use ($customers, $paymentMap, $details) {
            return [
                'id'                    => $inv->id,
                'invoice_no'            => $inv->invoice_no,
                'date'                  => $inv->created_at,
                'customer_id'           => $inv->customer_id,
                'customer_code'         => $customers[$inv->customer_id] ?? null,
                'driver_employeeid'     => $inv->driver_employeeid,
                'kelindan_employeeid'   => $inv->kelindan_employeeid,
                'agent_employeeid'      => $inv->agent_employeeid,
                'supervisor_employeeid' => $inv->supervisor_employeeid,
                'payment_term'          => $paymentMap[$inv->paymentterm] ?? 'Unknown',
                'amount'                => $inv->amount,
                'chequeno'              => $inv->chequeno,
                'payment_proof'         => $inv->paymentproof,
                'remark'                => $inv->remark,

                'details' => isset($details[$inv->id])
                    ? $details[$inv->id]->map(function ($d) {
                        return [
                            // all invoice_details columns
                            'id'                  => $d->id,
                            'invoice_id'          => $d->invoice_id,
                            'product_id'          => $d->product_id,
                            'quantity'            => $d->quantity,
                            'price'               => $d->price,
                            'amount'              => $d->amount,
                            'remark'              => $d->remark,
                            'product_name'        => $d->product_name,
                            'unit_code'           => $d->unit_code,
                            'classification_code' => $d->classification_code,
                        ];
                    })
                : [],
            ];
        });

        return response()->json([
            'status' => 'success',
            'count' => $data->count(),
            'data' => $data
        ]);
    }


    /**
     * Update invoice autocount status from webhook
     */
    public function update(Request $request)
    {
        $request->validate([
            'invoice_no' => 'required',
            'autocount_status' => 'required'
        ]);

        $invoice = Invoice::where('invoice_no', $request->invoice_no)->first();

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found'
            ], 404);
        }

        $invoice->autocount_status = $request->autocount_status;
        $invoice->save();

        return response()->json([
            'status' => 'ok'
        ]);
    }
}