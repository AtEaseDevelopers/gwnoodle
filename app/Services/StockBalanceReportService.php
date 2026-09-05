<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\ProductBatch;
use App\Models\ProductCost;
use App\Models\WarehouseInventoryBalance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockBalanceReportService
{
    /**
     * Generate stock balance report data
     * 
     * @param array $filters Optional filters (date, warehouse_id, product_id)
     * @return array
     */
    public function generateReport($filters = [])
    {
        $reportData = [
            'generated_at' => Carbon::now()->format('d/m/Y H:i:s'),
            'warehouses' => [],
            'summary' => [
                'total_products' => 0,
                'total_quantity' => 0,
                'total_value' => 0,
                'warehouses_count' => 0
            ]
        ];

        // Get active warehouses
        $warehouses = Warehouse::active()->get();
        $reportData['summary']['warehouses_count'] = $warehouses->count();

        // If specific warehouse is selected
        if (isset($filters['warehouse_id']) && $filters['warehouse_id']) {
            $warehouses = $warehouses->where('id', $filters['warehouse_id']);
        }

        // Get products with stock
        $products = $this->getProductsWithStock($filters);

        foreach ($warehouses as $warehouse) {
            $warehouseData = [
                'warehouse' => $warehouse,
                'products' => [],
                'total_quantity' => 0,
                'total_value' => 0,
                'product_count' => 0
            ];

            foreach ($products as $product) {
                // Get product batches with quantity in this warehouse
                $batches = $this->getProductBatchesInWarehouse($product->id, $warehouse->id, $filters);
                
                if ($batches->isEmpty() && (!isset($filters['show_zero_stock']) || !$filters['show_zero_stock'])) {
                    continue;
                }

                $productCost = $this->getLatestProductCost($product->id);
                $productData = $this->formatProductData($product, $batches, $productCost, $warehouse->id);
                
                if ($productData['total_quantity'] > 0 || (isset($filters['show_zero_stock']) && $filters['show_zero_stock'])) {
                    $warehouseData['products'][] = $productData;
                    $warehouseData['total_quantity'] += $productData['total_quantity'];
                    $warehouseData['total_value'] += $productData['total_value'];
                    $warehouseData['product_count']++;
                }
            }

            $reportData['warehouses'][] = $warehouseData;
            $reportData['summary']['total_products'] += $warehouseData['product_count'];
            $reportData['summary']['total_quantity'] += $warehouseData['total_quantity'];
            $reportData['summary']['total_value'] += $warehouseData['total_value'];
        }

        return $reportData;
    }

    /**
     * Get products that have stock (or all if show zero stock)
     */
    private function getProductsWithStock($filters = [])
    {
        $query = Product::where('status', 1); // Active products only

        if (isset($filters['product_id']) && $filters['product_id']) {
            $query->where('id', $filters['product_id']);
        }

        // Only get products that have stock in any warehouse if not showing zero stock
        if (!isset($filters['show_zero_stock']) || !$filters['show_zero_stock']) {
            $query->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('product_batches')
                    ->join('warehouse_inventory_balances', 'product_batches.id', '=', 'warehouse_inventory_balances.batch_id')
                    ->whereColumn('product_batches.product_id', 'products.id')
                    ->where('product_batches.quantity', '>', 0)
                    ->where(function ($q) {
                        $q->whereNull('product_batches.expiry_date')
                          ->orWhere('product_batches.expiry_date', '>', now());
                    })
                    ->where('product_batches.status', ProductBatch::STATUS_ACTIVE)
                    ->where('warehouse_inventory_balances.quantity', '>', 0);
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get product batches with quantities in specific warehouse
     */
    private function getProductBatchesInWarehouse($productId, $warehouseId, $filters = [])
    {
        $query = ProductBatch::with('product')
            ->where('product_id', $productId)
            ->where('status', ProductBatch::STATUS_ACTIVE)
            ->where('quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>', now());
            })
            ->whereHas('warehouseInventoryBalances', function($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                  ->where('quantity', '>', 0);
            });

        // Apply batch number filter if provided
        if (isset($filters['batch_no']) && $filters['batch_no']) {
            $query->where('batch_code', 'like', '%' . $filters['batch_no'] . '%');
        }

        return $query->orderBy('expiry_date', 'asc')->get();
    }

    /**
     * Get latest product cost from ProductCost model
     */
    private function getLatestProductCost($productId)
    {
        $latestCost = ProductCost::where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->first();

        // If no cost history exists, get from product's current cost
        if (!$latestCost) {
            $product = Product::find($productId);
            return $product ? $product->cost : 0;
        }

        return $latestCost->cost;
    }

    /**
     * Format product data for report display
     */
    private function formatProductData($product, $batches, $productCost, $warehouseId)
    {
        $totalQuantity = 0;
        $batchesData = [];

        foreach ($batches as $batch) {
            // Get quantity from warehouse inventory balance
            $warehouseBalance = WarehouseInventoryBalance::where('batch_id', $batch->id)
                ->where('warehouse_id', $warehouseId)
                ->first();

            $quantity = $warehouseBalance ? $warehouseBalance->quantity : 0;
            
            if ($quantity > 0) {
                $totalQuantity += $quantity;
                $batchesData[] = [
                    'batch_no' => $batch->batch_code,
                    'expiry_date' => $batch->expiry_date ? Carbon::parse($batch->expiry_date)->format('d-m-Y') : '-',
                    'quantity' => $quantity,
                    'smallest_bal_qty' => $this->getSmallestBalanceQuantity($product, $quantity)
                ];
            }
        }

        // If no batches with quantity found, show zero stock entry
        if (empty($batchesData) && $totalQuantity == 0) {
            $batchesData[] = [
                'batch_no' => 'N/A',
                'expiry_date' => 'N/A',
                'quantity' => 0,
                'smallest_bal_qty' => $this->getSmallestBalanceQuantity($product, 0)
            ];
        }

        $totalValue = $totalQuantity * $productCost;

        return [
            'product_id' => $product->id,
            'product_code' => $product->unit_code,
            'product_name' => $product->name,
            'average_cost' => $productCost,
            'total_quantity' => $totalQuantity,
            'total_value' => $totalValue,
            'batches' => $batchesData
        ];
    }

    /**
     * Calculate smallest balance quantity
     * This depends on your business logic - can be based on UOM conversion
     */
    private function getSmallestBalanceQuantity($product, $quantity)
    {
        if ($product->carton_enabled && $product->units_per_carton && $quantity > 0) {
            $cartons = floor($quantity / $product->units_per_carton);
            $looseUnits = $quantity % $product->units_per_carton;
            
            // Return the smallest unit representation (either cartons or units)
            if ($cartons > 0 && $looseUnits == 0) {
                return $cartons; // Return in cartons
            } elseif ($cartons == 0 && $looseUnits > 0) {
                return $looseUnits; // Return in units
            } else {
                return $quantity; // Return total units when mixed
            }
        }
        
        return $quantity; // Default to units
    }

    /**
     * Generate PDF for stock balance report
     */
    public function generatePDF($reportData, $format = 'A4')
    {
        $pdf = \PDF::loadView('reports.stock_balance_pdf', compact('reportData'));
        
        if ($format == 'A4') {
            $pdf->setPaper('A4', 'portrait');
        } else {
            $pdf->setPaper('A4', 'landscape');
        }
        
        return $pdf;
    }

    /**
     * Alternative method to get stock balance using direct queries (more efficient for large datasets)
     */
    public function generateReportOptimized($filters = [])
    {
        $reportData = [
            'generated_at' => Carbon::now()->format('d/m/Y H:i:s'),
            'warehouses' => [],
            'summary' => [
                'total_products' => 0,
                'total_quantity' => 0,
                'total_value' => 0,
                'warehouses_count' => 0
            ]
        ];

        // Get warehouses
        $warehouses = Warehouse::active();
        if (isset($filters['warehouse_id']) && $filters['warehouse_id']) {
            $warehouses->where('id', $filters['warehouse_id']);
        }
        $warehouses = $warehouses->get();
        $reportData['summary']['warehouses_count'] = $warehouses->count();

        // Build the query for stock balances
        $query = DB::table('warehouse_inventory_balances as wib')
            ->join('product_batches as pb', 'wib.batch_id', '=', 'pb.id')
            ->join('products as p', 'pb.product_id', '=', 'p.id')
            ->leftJoin('product_costs as pc', function($join) {
                $join->on('p.id', '=', 'pc.product_id')
                     ->whereRaw('pc.created_at = (SELECT MAX(created_at) FROM product_costs WHERE product_id = p.id)');
            })
            ->select(
                'wib.warehouse_id',
                'p.id as product_id',
                'p.unit_code',
                'p.name as product_name',
                'p.carton_enabled',
                'p.units_per_carton',
                'p.cost as current_cost',
                DB::raw('COALESCE(pc.cost, p.cost) as average_cost'),
                'pb.batch_code',
                'pb.expiry_date',
                'wib.quantity',
                DB::raw('SUM(wib.quantity) as total_quantity')
            )
            ->where('pb.status', ProductBatch::STATUS_ACTIVE)
            ->where('pb.quantity', '>=', 0)
            ->where(function ($q) {
                $q->whereNull('pb.expiry_date')->orWhere('pb.expiry_date', '>', now());
            });

        // Zero-balance rows are hidden by default - a depleted batch keeps
        // its warehouse_inventory_balances row at quantity 0, and there's
        // no need to clutter the report with stock nobody has. Pass
        // show_zero_stock to include them anyway.
        if (!isset($filters['show_zero_stock']) || !$filters['show_zero_stock']) {
            $query->where('wib.quantity', '>', 0);
        } else {
            $query->where('wib.quantity', '>=', 0);
        }

        // Apply filters
        if (isset($filters['product_id']) && $filters['product_id']) {
            $query->where('p.id', $filters['product_id']);
        }
        if (isset($filters['batch_no']) && $filters['batch_no']) {
            $query->where('pb.batch_code', 'like', '%' . $filters['batch_no'] . '%');
        }

        $results = $query->groupBy('wib.warehouse_id', 'p.id', 'p.unit_code', 'p.name', 'p.carton_enabled', 'p.units_per_carton', 'p.cost', 'pc.cost', 'pb.batch_code', 'pb.expiry_date', 'wib.quantity')
            ->orderBy('p.name')
            ->orderBy('pb.expiry_date')
            ->get();

        // Group results by warehouse
        $groupedByWarehouse = [];
        foreach ($results as $row) {
            $warehouseId = $row->warehouse_id;
            $productId = $row->product_id;
            $batchCode = $row->batch_code;
            
            if (!isset($groupedByWarehouse[$warehouseId])) {
                $groupedByWarehouse[$warehouseId] = [];
            }
            
            if (!isset($groupedByWarehouse[$warehouseId][$productId])) {
                $groupedByWarehouse[$warehouseId][$productId] = [
                    'product' => $row,
                    'batches' => [],
                    'total_quantity' => 0,
                    'total_value' => 0
                ];
            }
            
            $quantity = $row->quantity;
            $cost = $row->average_cost;
            
            $groupedByWarehouse[$warehouseId][$productId]['batches'][] = [
                'batch_no' => $row->batch_code,
                'expiry_date' => $row->expiry_date ? Carbon::parse($row->expiry_date)->format('d-m-Y') : '-',
                'quantity' => $quantity,
                'smallest_bal_qty' => $this->getSmallestBalanceQuantityFromRow($row, $quantity)
            ];
            
            $groupedByWarehouse[$warehouseId][$productId]['total_quantity'] += $quantity;
            $groupedByWarehouse[$warehouseId][$productId]['total_value'] += $quantity * $cost;
        }

        // Format the report data
        foreach ($warehouses as $warehouse) {
            $warehouseData = [
                'warehouse' => $warehouse,
                'products' => [],
                'total_quantity' => 0,
                'total_value' => 0,
                'product_count' => 0
            ];
            
            if (isset($groupedByWarehouse[$warehouse->id])) {
                foreach ($groupedByWarehouse[$warehouse->id] as $productId => $productData) {
                    $product = $productData['product'];
                    $warehouseData['products'][] = [
                        'product_id' => $productId,
                        'product_code' => $product->unit_code,
                        'product_name' => $product->product_name,
                        'average_cost' => $product->average_cost,
                        'total_quantity' => $productData['total_quantity'],
                        'total_value' => $productData['total_value'],
                        'batches' => $productData['batches']
                    ];
                    
                    $warehouseData['total_quantity'] += $productData['total_quantity'];
                    $warehouseData['total_value'] += $productData['total_value'];
                    $warehouseData['product_count']++;
                }
            }
            
            $reportData['warehouses'][] = $warehouseData;
            $reportData['summary']['total_products'] += $warehouseData['product_count'];
            $reportData['summary']['total_quantity'] += $warehouseData['total_quantity'];
            $reportData['summary']['total_value'] += $warehouseData['total_value'];
        }
        
        return $reportData;
    }

    /**
     * Helper method for optimized version
     */
    private function getSmallestBalanceQuantityFromRow($row, $quantity)
    {
        if ($row->carton_enabled && $row->units_per_carton && $quantity > 0) {
            $cartons = floor($quantity / $row->units_per_carton);
            $looseUnits = $quantity % $row->units_per_carton;
            
            if ($cartons > 0 && $looseUnits == 0) {
                return $cartons;
            } elseif ($cartons == 0 && $looseUnits > 0) {
                return $looseUnits;
            } else {
                return $quantity;
            }
        }
        
        return $quantity;
    }
}