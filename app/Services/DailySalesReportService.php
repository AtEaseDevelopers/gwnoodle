<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\InvoicePayment;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailySalesReportService
{
    /**
     * Generate daily sales report for a specific date
     * 
     * @param string $date Date in Y-m-d format
     * @param array $filters Optional filters (customer_id, payment_term)
     * @return array
     */
    public function generateReport($date, $filters = [])
    {
        $reportData = [
            'generated_at' => Carbon::now()->format('d/m/Y H:i:s'),
            'report_date' => Carbon::parse($date)->format('d/m/Y'),
            'report_no' => $this->generateReportNumber($date),
            'items' => [],
            'invoices' => [],
            'summary' => [
                'total_invoices' => 0,
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

        // Build query for invoices on the specific date
        $query = Invoice::with(['customer', 'invoicedetail.product', 'invoicepayment'])
            ->whereDate('date', $date)
            ->where('status', Invoice::STATUS_COMPLETED);

            // Apply filters
        if (isset($filters['customer_id']) && $filters['customer_id']) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['payment_term']) && $filters['payment_term']) {
            $query->where('paymentterm', $filters['payment_term']);
        }

        // Get invoices ordered by invoice number
        $invoices = $query->orderBy('invoiceno', 'asc')->get();

        $reportData['summary']['total_invoices'] = $invoices->count();
        
        $invoiceItems = [];
        $allProducts = [];
        $productCounter = 1;

        foreach ($invoices as $invoice) {
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
                
                // Track for product summary
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
            }
            
            // Calculate payments for this invoice
            $paidAmount = 0;
            $paymentMethods = [];
            foreach ($invoice->invoicepayment as $payment) {
                $paidAmount += $payment->amount;
                $method = $this->getPaymentMethodName($payment->type);
                $paymentMethods[$method] = ($paymentMethods[$method] ?? 0) + $payment->amount;
                
                // Update payment summary
                $this->updatePaymentSummary($reportData['payment_summary'], $payment->type, $payment->amount);
            }
            
            $outstanding = $invoiceTotal - $paidAmount;
            
            $invoiceData = [
                'invoice_no' => $invoice->invoiceno,
                'customer_name' => $invoice->customer ? $invoice->customer->name : 'N/A',
                'customer_code' => $invoice->customer ? $invoice->customer->code : 'N/A',
                'payment_term' => $this->getPaymentTermName($invoice->paymentterm),
                'driver' => $invoice->driver ? $invoice->driver->name : 'N/A',
                'total_amount' => $invoiceTotal,
                'paid_amount' => $paidAmount,
                'outstanding' => $outstanding,
                'payment_methods' => $paymentMethods,
                'items' => $invoiceItemsList,
            ];
            
            $invoiceItems[] = $invoiceData;
            
            $reportData['summary']['total_sales'] += $invoiceTotal;
            $reportData['summary']['total_paid'] += $paidAmount;
            $reportData['summary']['total_outstanding'] += $outstanding;
        }
        
        // Get unique customers count
        $uniqueCustomers = $invoices->pluck('customer_id')->unique()->count();
        $reportData['summary']['total_customers'] = $uniqueCustomers;
        $reportData['summary']['total_quantity'] = collect($allProducts)->sum('quantity');
        
        $reportData['invoices'] = $invoiceItems;
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