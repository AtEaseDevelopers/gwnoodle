<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockCardService
{
    /**
     * Generate stock card report for multiple products
     * 
     * @param array $productIds Array of product IDs
     * @param string $dateFrom Start date in Y-m-d format
     * @param string $dateTo End date in Y-m-d format
     * @param array $filters Optional filters (warehouse_id, batch_id)
     * @return array
     */
    public function generateReport($productIds, $dateFrom, $dateTo, $filters = [])
    {
        // Ensure productIds is an array
        $productIds = is_array($productIds) ? $productIds : [$productIds];
        
        $products = Product::whereIn('id', $productIds)->get();
        
        if ($products->isEmpty()) {
            return null;
        }

        $reportData = [
            'generated_at' => Carbon::now()->format('d/m/Y H:i:s'),
            'date_from' => Carbon::parse($dateFrom)->format('d/m/Y'),
            'date_to' => Carbon::parse($dateTo)->format('d/m/Y'),
            'report_no' => $this->generateReportNumber($productIds, $dateFrom, $dateTo),
            'warehouse_name' => null,
            'batch_info' => null,
            'products' => [],
            'summary' => [
                'total_products' => count($products),
                'total_stock_in' => 0,
                'total_stock_out' => 0,
                'total_net_change' => 0
            ]
        ];

        // Get warehouse if specified
        if (isset($filters['warehouse_id']) && !empty($filters['warehouse_id'])) {
            $warehouseIds = (array) $filters['warehouse_id'];
            $warehouses = Warehouse::whereIn('id', $warehouseIds)->get();
            $reportData['warehouse_name'] = $warehouses->pluck('name')->implode(', ');
        } else {
            $reportData['warehouse_name'] = 'All Warehouses';
        }

        // Get batch if specified
        if (isset($filters['batch_id']) && !empty($filters['batch_id'])) {
            $batchIds = (array) $filters['batch_id'];
            $batches = ProductBatch::whereIn('id', $batchIds)->get();
            $reportData['batch_info'] = $batches->map(function($batch) {
                return [
                    'code' => $batch->batch_code,
                    'expiry_date' => $batch->getFormattedExpiryDateAttribute(),
                    'original_quantity' => $batch->quantity
                ];
            })->toArray();
        }

        // Generate data for each product
        foreach ($products as $product) {
            $productData = $this->generateProductStockCard($product, $dateFrom, $dateTo, $filters);
            if ($productData) {
                $reportData['products'][] = $productData;
                
                // Update summary totals
                $reportData['summary']['total_stock_in'] += $productData['summary']['total_stock_in'];
                $reportData['summary']['total_stock_out'] += $productData['summary']['total_stock_out'];
            }
        }
        
        $reportData['summary']['total_net_change'] = $reportData['summary']['total_stock_in'] - $reportData['summary']['total_stock_out'];

        return $reportData;
    }

    /**
     * Generate stock card for a single product
     */
    private function generateProductStockCard($product, $dateFrom, $dateTo, $filters = [])
    {
        $productData = [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->unit_code,
                'uom' => $this->getUOMDisplay($product),
                'current_cost' => $product->cost,
                'current_stock' => $product->total_quantity
            ],
            'transactions' => [],
            'summary' => [
                'total_stock_in' => 0,
                'total_stock_out' => 0,
                'net_change' => 0,
                'opening_balance' => 0,
                'closing_balance' => 0
            ]
        ];

        // Build query for inventory transactions
        $query = InventoryTransaction::with(['product', 'batch', 'warehouse', 'customer', 'lorry'])
            ->where('product_id', $product->id)
            ->whereBetween('date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        // Apply warehouse filter
        if (isset($filters['warehouse_id']) && !empty($filters['warehouse_id'])) {
            $warehouseIds = (array) $filters['warehouse_id'];
            $query->whereIn('warehouse_id', $warehouseIds);
        }

        // Apply batch filter
        if (isset($filters['batch_id']) && !empty($filters['batch_id'])) {
            $batchIds = (array) $filters['batch_id'];
            $query->whereIn('batch_id', $batchIds);
        }

        // Get transactions ordered by date
        $transactions = $query->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Calculate opening balance (stock before date_from)
        $openingBalance = $this->calculateOpeningBalance($product->id, $dateFrom, $filters);
        $productData['summary']['opening_balance'] = $openingBalance;

        // Process transactions with running balance
        $runningBalance = $openingBalance;
        $items = [];
        $counter = 1;

        foreach ($transactions as $transaction) {
            $isStockIn = $transaction->type == InventoryTransaction::TYPE_STOCK_IN;
            $isStockOut = $transaction->type == InventoryTransaction::TYPE_STOCK_OUT;
            $quantity = abs($transaction->quantity);
            
            // Calculate FIFO quantity if needed (for stock out)
            $fifoQty = $quantity;
            $unitCost = $this->getUnitCost($transaction, $product);
            $totalCost = $quantity * $unitCost;
            
            // Update running balance
            if ($isStockIn) {
                $runningBalance += $quantity;
                $inOutQty = '+' . $quantity;
            } else {
                $runningBalance -= $quantity;
                $inOutQty = '-' . $quantity;
            }

            $item = [
                'no' => $counter++,
                'date' => $transaction->date ? Carbon::parse($transaction->date)->format('d/m/Y') : 'N/A',
                'type' => $this->getTransactionTypeName($transaction->type),
                'lorry_no' => $this->getLorryNumber($transaction), 
                'description' => $this->getTransactionDescription($transaction),
                'location' => $transaction->warehouse ? $transaction->warehouse->name : '-',
                'uom' => $productData['product']['uom'],
                'batch_no' => $transaction->batch ? $transaction->batch->batch_code : 'N/A',
                'in_out_qty' => $inOutQty,
                'quantity' => $quantity,
                'bf_qty' => $runningBalance - ($isStockIn ? $quantity : -$quantity),
                'fifo_qty' => $isStockOut ? $fifoQty : 0,
                'unit_cost' => $unitCost,
                'total_cost' => $isStockOut ? -$totalCost : $totalCost,
                'bif_cost' => $runningBalance * $unitCost,
                'bal_cost' => $runningBalance * $unitCost,
                'remark' => $transaction->remark ?? ''
            ];
            
            $items[] = $item;
            
            // Update summary
            if ($isStockIn) {
                $productData['summary']['total_stock_in'] += $quantity;
            } else {
                $productData['summary']['total_stock_out'] += $quantity;
            }
        }

        $productData['transactions'] = $items;
        $productData['summary']['net_change'] = $productData['summary']['total_stock_in'] - $productData['summary']['total_stock_out'];
        $productData['summary']['closing_balance'] = $runningBalance;

        return $productData;
    }

    /**
     * Calculate opening balance (stock before start date)
     */
    private function calculateOpeningBalance($productId, $dateFrom, $filters = [])
    {
        $query = InventoryTransaction::where('product_id', $productId)
            ->where('date', '<', $dateFrom . ' 00:00:00');

        // Apply filters for opening balance
        if (isset($filters['warehouse_id']) && !empty($filters['warehouse_id'])) {
            $warehouseIds = (array) $filters['warehouse_id'];
            $query->whereIn('warehouse_id', $warehouseIds);
        }
        if (isset($filters['batch_id']) && !empty($filters['batch_id'])) {
            $batchIds = (array) $filters['batch_id'];
            $query->whereIn('batch_id', $batchIds);
        }

        $transactions = $query->orderBy('date', 'asc')->get();
        
        $balance = 0;
        foreach ($transactions as $transaction) {
            if ($transaction->type == InventoryTransaction::TYPE_STOCK_IN) {
                $balance += abs($transaction->quantity);
            } else {
                $balance -= abs($transaction->quantity);
            }
        }
        
        return $balance;
    }

    /**
     * Get unit cost for transaction
     */
    private function getUnitCost($transaction, $product)
    {
        // Try to get from product cost if available
        if ($product && $product->cost) {
            return $product->cost;
        }
        
        // Default to 0 if no cost
        return 0;
    }

    /**
     * Get transaction type name
     */
    private function getTransactionTypeName($type)
    {
        return match($type) {
            InventoryTransaction::TYPE_STOCK_IN => 'SI',
            InventoryTransaction::TYPE_STOCK_OUT => 'SO',
            InventoryTransaction::TYPE_RETURN => 'RET',
            InventoryTransaction::TYPE_TRANSFER => 'TRF',
            InventoryTransaction::TYPE_STOCK_ADJUSTMENT => 'SA',
            default => 'N/A'
        };
    }

    private function getLorryNumber($transaction)
    {
        if ($transaction->lorry_id && $transaction->lorry) {
            return $transaction->lorry->lorryno ?? 'LOR-' . $transaction->lorryno;
        }
        return '-';
    }

    /**
     * Get document number from transaction
     */
    private function getDocumentNumber($transaction)
    {
        // Check if it's related to invoice
        if ($transaction->reference_type === 'App\\Models\\Invoice' && $transaction->reference) {
            return $transaction->reference->invoiceno;
        }
        
        // Check if it's related to lorry
        if ($transaction->lorry_id && $transaction->lorry) {
            return 'LOR-' . ($transaction->lorry->registration_no ?? $transaction->lorry_id);
        }
        
        // Return transaction ID as fallback
        return 'TXN-' . $transaction->id;
    }

    /**
     * Get transaction description
     */
    private function getTransactionDescription($transaction)
    {
        $descriptions = [];
        
        // Add customer info if available
        if ($transaction->customer_id && $transaction->customer) {
            $descriptions[] = $transaction->customer->name;
        }
        
        // Add remark
        if ($transaction->remark) {
            $descriptions[] = $transaction->remark;
        }
        
        // Add lorry info
        if ($transaction->lorry_id && $transaction->lorry) {
            $descriptions[] = 'Lorry: ' . $transaction->lorry->registration_no;
        }
        
        // Add batch info
        if ($transaction->batch_id && $transaction->batch) {
            $descriptions[] = 'Batch: ' . $transaction->batch->batch_code;
        }
        
        return implode(', ', $descriptions) ?: 'Transaction';
    }

    /**
     * Get UOM display based on product settings
     */
    private function getUOMDisplay($product)
    {
        
        return 'PCS';
    }

    /**
     * Generate report number
     */
    private function generateReportNumber($productIds, $dateFrom, $dateTo)
    {
        $dateFromObj = Carbon::parse($dateFrom);
        $dateToObj = Carbon::parse($dateTo);
        
        $fromStr = $dateFromObj->format('ymd');
        $toStr = $dateToObj->format('ymd');
        
        $productCount = count($productIds);
        $productPrefix = $productCount == 1 ? 'P' . $productIds[0] : 'P' . $productCount . 'Items';
        
        if ($dateFrom == $dateTo) {
            return 'SC-' . $productPrefix . '-' . $fromStr;
        }
        
        return 'SC-' . $productPrefix . '-' . $fromStr . '-' . $toStr;
    }

    /**
     * Generate PDF for stock card report
     */
    public function generatePDF($reportData, $format = 'A4')
    {
        // Use wkhtmltopdf (Snappy) — far faster and lighter than DomPDF on the large
        // transaction tables a stock card produces. Keep parity with stockCardReportView().
        $pdf = \Barryvdh\Snappy\Facades\SnappyPdf::loadView('reports.stock_card', compact('reportData'));

        $pdf->setPaper('a4')
            ->setOrientation($format == 'A4' ? 'landscape' : 'portrait')
            ->setOption('enable-local-file-access', true);

        return $pdf;
    }
}