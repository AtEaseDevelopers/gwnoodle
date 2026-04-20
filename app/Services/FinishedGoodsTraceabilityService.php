<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\WarehouseInventoryBalance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinishedGoodsTraceabilityService
{
    /**
     * Generate finished goods traceability report
     * 
     * @param string $dateFrom Start date in Y-m-d format
     * @param string $dateTo End date in Y-m-d format
     * @param array $filters Optional filters (customer_id, product_id, batch_id) - can be arrays
     * @return array
     */
    public function generateReport($dateFrom, $dateTo, $filters = [])
    {
        $reportData = [
            'generated_at' => Carbon::now()->format('d/m/Y H:i:s'),
            'date_from' => Carbon::parse($dateFrom)->format('d/m/Y'),
            'date_to' => Carbon::parse($dateTo)->format('d/m/Y'),
            'report_no' => $this->generateReportNumber($dateFrom, $dateTo),
            'items' => [],
            'summary' => [
                'total_deliveries' => 0,
                'total_quantity' => 0,
                'total_customers' => 0,
                'total_products' => 0,
                'total_batches' => 0
            ],
            'applied_filters' => [
                'customers' => [],
                'products' => [],
                'batches' => []
            ]
        ];

        // Build query for invoice details with related data
        $query = InvoiceDetail::with([
            'invoice.customer',
            'product',
            'batch',
            'warehouse'
        ])
        ->whereHas('invoice', function($q) use ($dateFrom, $dateTo, $filters) {
            $q->whereBetween('date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
              ->where('status', Invoice::STATUS_COMPLETED);
            
            // Apply customer filter (array support)
            if (isset($filters['customer_id']) && !empty($filters['customer_id'])) {
                $customerIds = (array) $filters['customer_id'];
                $q->whereIn('customer_id', $customerIds);
                
                // Store filter names for display
                $customers = Customer::whereIn('id', $customerIds)->get();
                foreach ($customers as $customer) {
                    $reportData['applied_filters']['customers'][] = $customer->name;
                }
            }
        });

        // Apply product filter (array support)
        if (isset($filters['product_id']) && !empty($filters['product_id'])) {
            $productIds = (array) $filters['product_id'];
            $query->whereIn('product_id', $productIds);
            
            // Store filter names for display
            $products = Product::whereIn('id', $productIds)->get();
            foreach ($products as $product) {
                $reportData['applied_filters']['products'][] = $product->name;
            }
        }

        // Apply batch filter (array support)
        if (isset($filters['batch_id']) && !empty($filters['batch_id'])) {
            $batchIds = (array) $filters['batch_id'];
            $query->whereIn('product_batch_id', $batchIds);
            // Store filter names for display
            $batches = ProductBatch::whereIn('id', $batchIds)->get();

            foreach ($batches as $batch) {
                $reportData['applied_filters']['batches'][] = $batch->batch_code;
            }
        }

        // Get deliveries ordered by date, then customer, then product
        $deliveries = $query->orderBy('created_at', 'desc')
            ->orderBy('invoice_id', 'desc')
            ->get();

        $items = [];
        $uniqueCustomers = [];
        $uniqueProducts = [];
        $uniqueBatches = [];

        foreach ($deliveries as $delivery) {
            $invoice = $delivery->invoice;
            $customer = $invoice ? $invoice->customer : null;
            $product = $delivery->product;
            $batch = $delivery->batch;
            
            if (!$invoice || !$product) continue;

            $item = [
                'delivery_date' => $invoice->date ? Carbon::parse($invoice->date)->format('d/m/Y') : 'N/A',
                'invoice_no' => $invoice->invoiceno ?? 'N/A',
                'customer_name' => $customer ? $customer->company : 'N/A',
                'customer_code' => $customer ? $customer->code : 'N/A',
                'customer_phone' => $customer ? $customer->phone : 'N/A',
                'product_name' => $product->name,
                'product_code' => $product->unit_code,
                'product_description' => $product->description ?? $product->name,
                'quantity' => $delivery->quantity,
                'uom' => $this->getUOMDisplay($product),
                'batch_code' => $batch ? $batch->batch_code : 'N/A',
                'expiry_date' => $batch ? $batch->getFormattedExpiryDateAttribute() : 'N/A',
                'warehouse' => $delivery->warehouse ? $delivery->warehouse->name : $this->getWarehouseForBatch($batch->id ?? null),
                'price' => $delivery->price,
                'total_price' => $delivery->totalprice,
            ];
            
            $items[] = $item;
            
            // Track unique values for summary
            if ($customer) {
                $uniqueCustomers[$customer->id] = $customer->name;
            }
            $uniqueProducts[$product->id] = $product->name;
            if ($batch) {
                $uniqueBatches[$batch->id] = $batch->batch_code;
            }
        }

        $reportData['items'] = $items;
        $reportData['summary']['total_deliveries'] = count($items);
        $reportData['summary']['total_quantity'] = collect($items)->sum('quantity');
        $reportData['summary']['total_customers'] = count($uniqueCustomers);
        $reportData['summary']['total_products'] = count($uniqueProducts);
        $reportData['summary']['total_batches'] = count($uniqueBatches);

        return $reportData;
    }

    public static function getWarehouseForBatch($productBatchId)
    {
        $balances = WarehouseInventoryBalance::where('batch_id', $productBatchId)
            ->with('warehouse')
            ->get();
        
        $warehouseNames = $balances->map(function($balance) {
            return $balance->warehouse ? $balance->warehouse->name : null;
        })->filter()->unique();
        
        if ($warehouseNames->count() === 1) {
            return $warehouseNames->first();
        }
        
        return $warehouseNames->implode(', ');
    }

    /**
     * Generate traceability report for a specific batch (or multiple batches)
     * Shows where specific batches were delivered
     */
    public function generateBatchTraceabilityReport($batchIds, $dateFrom = null, $dateTo = null)
    {
        // Ensure batchIds is an array
        $batchIds = is_array($batchIds) ? $batchIds : [$batchIds];
        
        $batches = ProductBatch::with('product')->whereIn('id', $batchIds)->get();
        
        if ($batches->isEmpty()) {
            return null;
        }
        
        $filters = ['batch_id' => $batchIds];
        
        if ($dateFrom && $dateTo) {
            $reportData = $this->generateReport($dateFrom, $dateTo, $filters);
        } else {
            // If no date range, get all deliveries for these batches
            $reportData = $this->generateReport('2000-01-01', date('Y-m-d'), $filters);
        }
        
        $reportData['batch_info'] = [];
        foreach ($batches as $batch) {
            $reportData['batch_info'][] = [
                'batch_code' => $batch->batch_code,
                'product_name' => $batch->product ? $batch->product->name : 'N/A',
                'product_code' => $batch->product ? $batch->product->unit_code : 'N/A',
                'expiry_date' => $batch->getFormattedExpiryDateAttribute(),
                'original_quantity' => $batch->quantity,
                'status' => $batch->getStatusTextAttribute()
            ];
        }
        
        return $reportData;
    }

    /**
     * Generate traceability report for a specific customer (or multiple customers)
     * Shows all deliveries to specific customers
     */
    public function generateCustomerTraceabilityReport($customerIds, $dateFrom = null, $dateTo = null)
    {
        // Ensure customerIds is an array
        $customerIds = is_array($customerIds) ? $customerIds : [$customerIds];
        
        $customers = Customer::whereIn('id', $customerIds)->get();
        
        if ($customers->isEmpty()) {
            return null;
        }
        
        $filters = ['customer_id' => $customerIds];
        
        if (!$dateFrom) $dateFrom = date('Y-m-d', strtotime('-30 days'));
        if (!$dateTo) $dateTo = date('Y-m-d');
        
        $reportData = $this->generateReport($dateFrom, $dateTo, $filters);
        
        $reportData['customer_info'] = [];
        foreach ($customers as $customer) {
            $reportData['customer_info'][] = [
                'customer_name' => $customer->company,
                'customer_code' => $customer->code,
                'customer_phone' => $customer->phone,
                'customer_address' => $customer->address ?? 'N/A'
            ];
        }
        
        return $reportData;
    }

    /**
     * Generate report number based on date range
     */
    private function generateReportNumber($dateFrom, $dateTo)
    {
        $dateFromObj = Carbon::parse($dateFrom);
        $dateToObj = Carbon::parse($dateTo);
        
        $fromStr = $dateFromObj->format('ymd');
        $toStr = $dateToObj->format('ymd');
        
        // Get count of deliveries in this date range
        $count = InvoiceDetail::whereHas('invoice', function($q) use ($dateFrom, $dateTo) {
            $q->whereBetween('date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
              ->where('status', Invoice::STATUS_COMPLETED);
        })->count();
        
        $sequence = str_pad(($count > 0 ? $count : 1), 3, '0', STR_PAD_LEFT);
        
        if ($dateFrom == $dateTo) {
            return 'FGT' . $fromStr . '-' . $sequence;
        }
        
        return 'FGT' . $fromStr . '-' . $toStr . '-' . $sequence;
    }

    /**
     * Get UOM display based on product settings
     */
    private function getUOMDisplay($product)
    {
        if ($product->carton_enabled && $product->units_per_carton) {
            return 'CTN';
        }
        return 'PCS';
    }

    /**
     * Generate PDF for traceability report
     */
    public function generatePDF($reportData, $format = 'A4')
    {
        $pdf = \PDF::loadView('reports.finished_goods_traceability', compact('reportData'));
        
        if ($format == 'A4') {
            $pdf->setPaper('A4', 'landscape');
        } else {
            $pdf->setPaper('A4', 'portrait');
        }
        
        return $pdf;
    }
}