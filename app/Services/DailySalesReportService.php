<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\InvoicePayment;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailySalesReportService
{
    /**
     * Generate daily sales report for a specific date grouped by trip
     * 
     * @param string $date Date in Y-m-d format
     * @param array $filters Optional filters (customer_id, driver_id, trip_uuid)
     * @return array
     */
    public function generateReport($date, $filters = [])
    {
        $reportData = [
            'generated_at' => Carbon::now()->format('d/m/Y H:i:s'),
            'report_date' => Carbon::parse($date)->format('d/m/Y'),
            'report_no' => $this->generateReportNumber($date),
            'trips' => [], // Changed from 'invoices' to 'trips'
            'summary' => [
                'total_invoices' => 0,
                'total_quantity' => 0,
                'total_sales' => 0,
                'total_paid' => 0,
                'total_outstanding' => 0,
                'total_customers' => 0,
                'total_trips' => 0
            ],
            'payment_summary' => [
                'cash' => 0,
                'credit' => 0,
                'online' => 0,
                'tng' => 0,
                'cheque' => 0
            ],
            'filters_applied' => [
                'driver' => 'All Drivers',
                'trip' => 'All Trips'
            ]
        ];

        // Build query for invoices on the specific date
        $query = Invoice::with(['customer', 'invoicedetail.product', 'invoicepayment', 'driver'])
            ->whereDate('date', $date)
            ->where('status', Invoice::STATUS_COMPLETED);

        // Apply driver filter
        if (isset($filters['driver_id']) && $filters['driver_id']) {
            if (is_array($filters['driver_id'])) {
                $query->whereIn('driver_id', $filters['driver_id']);
                
                $driverNames = \App\Models\Driver::whereIn('id', $filters['driver_id'])
                    ->pluck('name')
                    ->toArray();
                $reportData['filters_applied']['driver'] = implode(', ', $driverNames);
                $reportData['driver_filter_display'] = $driverNames;
                $reportData['driver_filter_ids'] = $filters['driver_id'];
            } else {
                $query->where('driver_id', $filters['driver_id']);
                
                $driver = \App\Models\Driver::find($filters['driver_id']);
                $driverName = $driver ? $driver->name : 'Unknown Driver';
                $reportData['filters_applied']['driver'] = $driverName;
                $reportData['driver_filter_display'] = [$driverName];
                $reportData['driver_filter_ids'] = [$filters['driver_id']];
            }
        } else {
            $reportData['driver_filter_display'] = [];
            $reportData['driver_filter_ids'] = [];
        }

        // Apply trip_uuid filter
        if (isset($filters['trip_uuid']) && $filters['trip_uuid']) {
            if (is_array($filters['trip_uuid'])) {
                $query->whereIn('trip_uuid', $filters['trip_uuid']);
                $reportData['filters_applied']['trip'] = implode(', ', $filters['trip_uuid']);
                $reportData['trip_filter_display'] = $filters['trip_uuid'];
            } else {
                $query->where('trip_uuid', $filters['trip_uuid']);
                $reportData['filters_applied']['trip'] = $filters['trip_uuid'];
                $reportData['trip_filter_display'] = [$filters['trip_uuid']];
            }
        }

        // Get invoices ordered by trip_uuid and invoice number
        $invoices = $query->orderBy('trip_uuid')->orderBy('invoiceno', 'asc')->get();
        
        // Group invoices by trip_uuid
        $groupedByTrip = $invoices->groupBy('trip_uuid');
        $reportData['summary']['total_trips'] = $groupedByTrip->count();
        $reportData['summary']['total_invoices'] = $invoices->count();

        $allProducts = [];
        $productCounter = 1;
        $tripCounter = 1;

        foreach ($groupedByTrip as $tripUuid => $tripInvoices) {
            $tripData = [
                'trip_no' => $tripCounter++,
                'trip_uuid' => $tripUuid ?? 'N/A',
                'trip_display' => $tripUuid ?? 'No Trip Assigned',
                'invoices' => [],
                'summary' => [
                    'total_invoices' => $tripInvoices->count(),
                    'total_quantity' => 0,
                    'total_sales' => 0,
                    'total_paid' => 0,
                    'total_outstanding' => 0,
                    'total_customers' => 0
                ],
                'payment_summary' => [
                    'cash' => 0,
                    'credit' => 0,
                    'online' => 0,
                    'tng' => 0,
                    'cheque' => 0
                ]
            ];

            $invoiceItems = [];
            $tripCustomers = [];

            foreach ($tripInvoices as $invoice) {
                $invoiceTotal = 0;
                $invoiceItemsList = [];
                
                foreach ($invoice->invoicedetail as $detail) {
                    $product = $detail->product;
                    $totalPrice = $detail->quantity * $detail->price;
                    $invoiceTotal += $totalPrice;
                    
                    $invoiceItemsList[] = [
                        'product_name' => $product ? $product->name : 'N/A',
                        'product_code' => $product ? $product->unit_code : 'N/A',
                        'quantity' => $detail->quantity,
                        'price' => $detail->price,
                        'total' => $totalPrice,
                    ];
                    
                    // Track for product summary (overall)
                    $productKey = $product ? $product->id : 'unknown';
                    if (!isset($allProducts[$productKey])) {
                        $allProducts[$productKey] = [
                            'no' => $productCounter++,
                            'product_name' => $product ? $product->name : 'N/A',
                            'product_code' => $product ? $product->unit_code : 'N/A',
                            'quantity' => 0,
                            'total_sales' => 0,
                            'invoices' => []
                        ];
                    }
                    $allProducts[$productKey]['quantity'] += $detail->quantity;
                    $allProducts[$productKey]['total_sales'] += $totalPrice;
                    $allProducts[$productKey]['invoices'][] = $invoice->invoiceno;
                    
                    // Track for trip summary
                    $tripData['summary']['total_quantity'] += $detail->quantity;
                }
                
                // Calculate payments for this invoice - only count approved
                // payments (status == 1); a pending/rejected row was never
                // actually collected and must not inflate the totals.
                $paidAmount = 0;
                $paymentMethods = [];
                foreach ($invoice->invoicepayment->where('status', 1) as $payment) {
                    $paidAmount += $payment->amount;
                    $method = $this->getPaymentMethodName($payment->type);
                    $paymentMethods[$method] = ($paymentMethods[$method] ?? 0) + $payment->amount;
                    
                    // Update payment summary (overall)
                    $this->updatePaymentSummary($reportData['payment_summary'], $payment->type, $payment->amount);
                    
                    // Update trip payment summary
                    $this->updatePaymentSummary($tripData['payment_summary'], $payment->type, $payment->amount);
                }
                
                $outstanding = $invoiceTotal - $paidAmount;
                
                $invoiceData = [
                    'invoice_no' => $invoice->invoiceno,
                    'customer_name' => $invoice->customer ? $invoice->customer->name : 'N/A',
                    'customer_code' => $invoice->customer ? $invoice->customer->code : 'N/A',
                    'customer_id' => $invoice->customer_id,
                    'payment_term' => $this->getPaymentTermName($invoice->paymentterm),
                    'driver' => $invoice->driver ? $invoice->driver->name : 'N/A',
                    'driver_id' => $invoice->driver_id,
                    'total_amount' => $invoiceTotal,
                    'paid_amount' => $paidAmount,
                    'outstanding' => $outstanding,
                    'payment_methods' => $paymentMethods,
                    'items' => $invoiceItemsList,
                ];
                
                $invoiceItems[] = $invoiceData;
                
                // Track customers for this trip
                if ($invoice->customer_id && !in_array($invoice->customer_id, $tripCustomers)) {
                    $tripCustomers[] = $invoice->customer_id;
                }
                
                // Update trip summary
                $tripData['summary']['total_sales'] += $invoiceTotal;
                $tripData['summary']['total_paid'] += $paidAmount;
                $tripData['summary']['total_outstanding'] += $outstanding;
                
                // Update overall summary
                $reportData['summary']['total_sales'] += $invoiceTotal;
                $reportData['summary']['total_paid'] += $paidAmount;
                $reportData['summary']['total_outstanding'] += $outstanding;
            }
            
            // Get unique customers count for this trip
            $tripData['summary']['total_customers'] = count($tripCustomers);
            $tripData['invoices'] = $invoiceItems;
            
            $reportData['trips'][] = $tripData;
        }
        
        // Get unique customers count overall
        $uniqueCustomers = $invoices->pluck('customer_id')->unique()->count();
        $reportData['summary']['total_customers'] = $uniqueCustomers;
        $reportData['summary']['total_quantity'] = collect($allProducts)->sum('quantity');
        
        $reportData['products'] = array_values($allProducts);

        return $reportData;
    }

    /**
     * Flat product+quantity summary over a date range - every invoice in
     * range, no driver/customer/trip filtering, no trip/invoice breakdown,
     * just the aggregated totals. Used by the Product Quantity Sold Report.
     * Kept as its own method rather than adding a range branch to
     * generateReport(), so Daily Sales Report's behavior can't be affected
     * by this change.
     */
    public function generateReportByDateRange($dateFrom, $dateTo)
    {
        $reportData = [
            'generated_at' => Carbon::now()->format('d/m/Y H:i:s'),
            'report_date' => Carbon::parse($dateFrom)->format('d/m/Y') . ' - ' . Carbon::parse($dateTo)->format('d/m/Y'),
            'report_no' => $this->generateReportNumber($dateFrom),
            'summary' => [
                'total_invoices' => 0,
                'total_quantity' => 0,
                'total_customers' => 0,
                'total_trips' => 0
            ],
        ];

        $invoices = Invoice::with(['invoicedetail.product'])
            ->whereBetween('date', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ])
            ->where('status', Invoice::STATUS_COMPLETED)
            ->get();

        $reportData['summary']['total_trips'] = $invoices->pluck('trip_uuid')->filter()->unique()->count();
        $reportData['summary']['total_invoices'] = $invoices->count();
        $reportData['summary']['total_customers'] = $invoices->pluck('customer_id')->unique()->count();

        $allProducts = [];
        $productCounter = 1;

        foreach ($invoices as $invoice) {
            foreach ($invoice->invoicedetail as $detail) {
                $product = $detail->product;
                // Group by name, not product_id - the same product name can
                // exist under multiple product records with different SKU
                // codes (different pack sizes etc.), and those should combine
                // into one line here rather than appearing as separate rows.
                $productKey = $product ? trim($product->name) : 'unknown';

                if (!isset($allProducts[$productKey])) {
                    $allProducts[$productKey] = [
                        'no' => $productCounter++,
                        'product_name' => $product ? $product->name : 'N/A',
                        'product_code' => $product ? $product->unit_code : 'N/A',
                        'quantity' => 0,
                    ];
                }
                $allProducts[$productKey]['quantity'] += $detail->quantity;
            }
        }

        // Sort by product name for a stable, readable report order
        usort($allProducts, fn($a, $b) => strcmp($a['product_name'], $b['product_name']));
        foreach ($allProducts as $i => $product) {
            $allProducts[$i]['no'] = $i + 1;
        }

        $reportData['summary']['total_quantity'] = collect($allProducts)->sum('quantity');
        $reportData['products'] = array_values($allProducts);

        return $reportData;
    }

    /**
     * Generate report number based on date
     * Format: DSR{YYMMDD}-{sequence}
     */
    private function generateReportNumber($date)
    {
        $dateObj = Carbon::parse($date);
        $dateStr = $dateObj->format('ymd');
        
        // Get count of invoices for this date
        $count = Invoice::whereDate('date', $date)
            ->where('status', Invoice::STATUS_COMPLETED)
            ->count();
        
        $sequence = str_pad(($count > 0 ? $count : 1), 3, '0', STR_PAD_LEFT);
        
        return 'DSR' . $dateStr . '-' . $sequence;
    }

    /**
     * Get payment method name
     */
    private function getPaymentMethodName($type)
    {
        return match($type) {
            Invoice::PAYMENT_TERM_CASH => 'Cash',
            Invoice::PAYMENT_TERM_CREDIT => 'Credit',
            Invoice::PAYMENT_TERM_ONLINE => 'Online',
            Invoice::PAYMENT_TERM_TNG => 'Touch n Go',
            Invoice::PAYMENT_TERM_CHEQUE => 'Cheque',
            default => 'Other'
        };
    }

    /**
     * Get payment term name
     */
    private function getPaymentTermName($term)
    {
        return match($term) {
            Invoice::PAYMENT_TERM_CASH => 'Cash',
            Invoice::PAYMENT_TERM_CREDIT => 'Credit',
            Invoice::PAYMENT_TERM_ONLINE => 'Online',
            Invoice::PAYMENT_TERM_TNG => 'Touch n Go',
            Invoice::PAYMENT_TERM_CHEQUE => 'Cheque',
            default => 'Unknown'
        };
    }

    /**
     * Update payment summary
     */
    private function updatePaymentSummary(&$summary, $type, $amount)
    {
        switch($type) {
            case Invoice::PAYMENT_TERM_CASH:
                $summary['cash'] += $amount;
                break;
            case Invoice::PAYMENT_TERM_CREDIT:
                $summary['credit'] += $amount;
                break;
            case Invoice::PAYMENT_TERM_ONLINE:
                $summary['online'] += $amount;
                break;
            case Invoice::PAYMENT_TERM_TNG:
                $summary['tng'] += $amount;
                break;
            case Invoice::PAYMENT_TERM_CHEQUE:
                $summary['cheque'] += $amount;
                break;
        }
    }

    /**
     * Generate PDF for daily sales report
     */
    public function generatePDF($reportData, $format = 'A4')
    {
        $pdf = \PDF::loadView('reports.daily_sales', compact('reportData'));
        
        if ($format == 'A4') {
            $pdf->setPaper('A4', 'portrait');
        } else {
            $pdf->setPaper('A4', 'landscape');
        }
        
        return $pdf;
    }
}