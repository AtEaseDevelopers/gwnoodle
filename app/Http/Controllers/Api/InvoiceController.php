<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Invoice;

class InvoiceController extends Controller
{

    /**
     * Get all pending invoices for AutoCount sync
     */
    public function syncPending()
    {
        $invoices = Invoice::where('status', 1)
            ->where('autocount_status', 'pending')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $invoices
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