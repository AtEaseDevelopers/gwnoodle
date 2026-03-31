<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockReceivedReportService
{
    /**
     * Generate stock received report for a date range
     * 
     * @param string $dateFrom Start date in Y-m-d format
     * @param string $dateTo End date in Y-m-d format
     * @param array $filters Optional filters (warehouse_id, product_id)
     * @return array
     */
    public function generateReport($dateFrom, $dateTo, $filters = [])
    {
        $reportData = [
            'generated_at' => Carbon::now()->format('d/m/Y H:i:s'),
            'date_from' => Carbon::parse($dateFrom)->format('d/m/Y'),
            'date_to' => Carbon::parse($dateTo)->format('d/m/Y'),
            'report_no' => $this->generateReportNumber($dateFrom, $dateTo),
            'reference_no' => isset($filters['reference_no']) ? $filters['reference_no'] : 'PRODUCTION',
            'warehouse_names' => [],
            'items' => [],
            'summary' => [
                'total_items' => 0,
                'total_quantity' => 0,
                'total_products' => 0
            ]
        ];

        // Handle warehouse filters (can be array or single)
        $warehouseIds = isset($filters['warehouse_id']) ? $filters['warehouse_id'] : null;
        $productIds = isset($filters['product_id']) ? $filters['product_id'] : null;
        
        // Get warehouse names for display
        if ($warehouseIds && !empty($warehouseIds)) {
            $warehouses = Warehouse::whereIn('id', (array)$warehouseIds)->get();
            $reportData['warehouse_names'] = $warehouses->pluck('name')->toArray();
        } else {
            $reportData['warehouse_names'] = ['All Warehouses'];
        }

        // Build query for stock received transactions within date range
        $query = InventoryTransaction::with(['product', 'batch', 'warehouse'])
            ->whereBetween('date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('type', InventoryTransaction::TYPE_STOCK_IN) // Stock In transactions
            ->where('stock_received', true); // Only stock received from production

        // Apply warehouse filter (if array, use whereIn)
        if ($warehouseIds && !empty($warehouseIds)) {
            $query->whereIn('warehouse_id', (array)$warehouseIds);
        }

        // Apply product filter (if array, use whereIn)
        if ($productIds && !empty($productIds)) {
            $query->whereIn('product_id', (array)$productIds);
        }

        // Get transactions ordered by date
        $transactions = $query->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Group by product, batch, and warehouse (each batch in each warehouse is separate)
        $groupedItems = [];
        $itemCounter = 1;

        foreach ($transactions as $transaction) {
            $product = $transaction->product;
            $batch = $transaction->batch;
            $warehouse = $transaction->warehouse;
            
            if (!$product) continue;

            // Create unique key combining product_id, batch_id, and warehouse_id
            // This ensures each batch in each warehouse is treated as a separate line item
            $batchId = $batch ? $batch->id : 'no_batch';
            $warehouseId = $warehouse ? $warehouse->id : 'no_warehouse';
            $key = $product->id . '_' . $batchId . '_' . $warehouseId;
            
            if (!isset($groupedItems[$key])) {
                $groupedItems[$key] = [
                    'no' => $itemCounter++,
                    'product_id' => $product->id,
                    'product_code' => $product->unit_code,
                    'product_name' => $product->name,
                    'quantity' => 0,
                    'uom' => $this->getUOMDisplay($product),
                    'batch_code' => $batch ? $batch->batch_code : 'N/A',
                    'batch_id' => $batchId,
                    'expiry_date' => $batch ? $batch->getFormattedExpiryDateAttribute() : 'N/A',
                    'warehouse' => $warehouse ? $warehouse->name : 'N/A',
                    'warehouse_id' => $warehouseId,
                    'remark' => $transaction->remark ?? 'Production Transfer',
                    'transaction_date' => $transaction->date ? Carbon::parse($transaction->date)->format('d/m/Y') : 'N/A',
                    'transactions' => []
                ];
            }
            
            // Sum quantities for this specific batch in this specific warehouse
            $groupedItems[$key]['quantity'] += abs($transaction->quantity);
            $groupedItems[$key]['transactions'][] = $transaction;
            
            $reportData['summary']['total_quantity'] += abs($transaction->quantity);
        }

        // Sort items by product name, then by batch code, then by warehouse
        $reportData['items'] = collect(array_values($groupedItems))
            ->sortBy([
                ['product_name', 'asc'],
                ['batch_code', 'asc'],
                ['warehouse', 'asc']
            ])
            ->values()
            ->all();
        
        // Re-number items after sorting
        foreach ($reportData['items'] as $index => $item) {
            $reportData['items'][$index]['no'] = $index + 1;
        }
        
        $reportData['summary']['total_items'] = count($reportData['items']);
        $reportData['summary']['total_products'] = count($reportData['items']);

        return $reportData;
    }

    /**
     * Generate report number based on date range
     * Format: SR{YYMMDD}-{YYMMDD}-{sequence}
     */
    private function generateReportNumber($dateFrom, $dateTo)
    {
        $dateFromObj = Carbon::parse($dateFrom);
        $dateToObj = Carbon::parse($dateTo);
        
        $fromStr = $dateFromObj->format('ymd');
        $toStr = $dateToObj->format('ymd');
        
        // Get count of transactions in this date range
        $count = InventoryTransaction::whereBetween('date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('type', InventoryTransaction::TYPE_STOCK_IN)
            ->where('stock_received', true)
            ->count();
        
        $sequence = str_pad(($count > 0 ? $count : 1), 3, '0', STR_PAD_LEFT);
        
        if ($dateFrom == $dateTo) {
            return 'SR' . $fromStr . '-' . $sequence;
        }
        
        return 'SR' . $fromStr . '-' . $toStr . '-' . $sequence;
    }

    /**
     * Get UOM display based on product settings
     */
    private function getUOMDisplay($product)
    {
        if ($product->carton_enabled && $product->units_per_carton) {
            return 'CTN';
        }
        return 'UNIT';
    }

    /**
     * Generate PDF for stock received report
     */
    public function generatePDF($reportData, $format = 'A4')
    {
        $pdf = \PDF::loadView('reports.stock_received_pdf', compact('reportData'));
        
        if ($format == 'A4') {
            $pdf->setPaper('A4', 'portrait');
        } else {
            $pdf->setPaper('A4', 'landscape');
        }
        
        return $pdf;
    }
}