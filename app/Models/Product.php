<?php

namespace App\Models;
use App\Traits\LogsActivity;

use Eloquent as Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    use LogsActivity;

    public $table = 'products';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'unit_code',
        'name',
        'price',           // Selling price
        'cost',           
        'status',
        'type',
        'classification_code',
        'carton_enabled',
        'units_per_carton'
    ];

    protected $casts = [
        'id' => 'integer',
        'unit_code' => 'string',
        'name' => 'string',
        'price' => 'float',
        'cost' => 'float',      
        'status' => 'integer',
        'type' => 'integer',
        'classification_code' => 'string',
        'carton_enabled' => 'boolean',
        'units_per_carton' => 'integer'
    ];

    public static $rules = [
        'unit_code' => 'required|string|max:255',
        'name' => 'required|string|max:255',
        'price' => 'required|numeric',
        'cost' => 'nullable|numeric|min:0',  
        'status' => 'required',
        'type' => 'nullable',
        'carton_enabled' => 'boolean',
        'units_per_carton' => 'nullable|integer|min:1|required_if:carton_enabled,1',
        'created_at' => 'nullable',
        'updated_at' => 'nullable'
    ];
    
    // Relationships
    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }
    
    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }
    
    // Cost history relationship
    public function costHistory()
    {
        return $this->hasMany(ProductCost::class)->orderBy('created_at', 'desc');
    }
    
    /**
     * =============================================
     * INVENTORY SUMMARY ATTRIBUTES - FIXED
     * =============================================
     */
    
    /**
     * Get total quantity across all active batches (current available stock)
     * This is the most important - current inventory from batches
     */
    public function getTotalQuantityAttribute()
    {
        return $this->batches()
            ->where('status', 1) // Active batches only
            ->where('expiry_date', '>', now()) // Not expired
            ->where('quantity', '>', 0) // Has stock
            ->sum('quantity');
    }
    
    /**
     * Get total quantity across all batches (including expired/depleted)
     * For historical/reporting purposes
     */
    public function getTotalQuantityAllAttribute()
    {
        return $this->batches()->sum('quantity');
    }
    
    /**
     * Get total quantity including expired batches (but still in system)
     * Useful for knowing total ever received
     */
    public function getTotalQuantityIncludingExpiredAttribute()
    {
        return $this->batches()
            ->where('quantity', '>', 0)
            ->sum('quantity');
    }
    
    /**
     * Get number of active batches with stock
     */
    public function getActiveBatchesCountAttribute()
    {
        return $this->batches()
            ->where('status', 1)
            ->where('quantity', '>', 0)
            ->where('expiry_date', '>', now())
            ->count();
    }
    
    /**
     * Get total batches count (all statuses)
     */
    public function getTotalBatchesCountAttribute()
    {
        return $this->batches()->count();
    }
    
    /**
     * Get total stock in (all time) - from transactions
     * This stays from transactions as it's historical
     */
    public function getTotalStockInAttribute()
    {
        return $this->inventoryTransactions()
            ->where('type', 1) // Stock In
            ->sum('quantity');
    }
    
    /**
     * Get total stock out (all time) - from transactions
     * This stays from transactions as it's historical
     */
    public function getTotalStockOutAttribute()
    {
        return abs($this->inventoryTransactions()
            ->where('type', 2) // Stock Out
            ->sum('quantity'));
    }
    
    /**
     * Get current stock value (total quantity * current cost)
     */
    public function getCurrentStockValueAttribute()
    {
        return $this->total_quantity * ($this->cost ?? 0);
    }
    
    /**
     * Get expiring soon quantity (within next X days)
     * From batches, not transactions
     */
    public function getExpiringSoonQuantityAttribute($days = 30)
    {
        return $this->batches()
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now())
            ->where('quantity', '>', 0)
            ->sum('quantity');
    }
    
    /**
     * Get expired quantity (stock that is expired but still in system)
     * From batches
     */
    public function getExpiredQuantityAttribute()
    {
        return $this->batches()
            ->where('expiry_date', '<', now())
            ->where('quantity', '>', 0)
            ->sum('quantity');
    }
    
    /**
     * Get depleted batches count
     */
    public function getDepletedBatchesCountAttribute()
    {
        return $this->batches()
            ->where('status', 3)
            ->orWhere('quantity', 0)
            ->count();
    }
    
    /**
     * =============================================
     * HELPER METHODS FOR BATCH MANAGEMENT
     * =============================================
     */
    
    /**
     * Get all batches with detailed information
     */
    public function getBatchSummaryAttribute()
    {
        return $this->batches()
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->map(function($batch) {
                return [
                    'id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'expiry_date' => $batch->expiry_date->format('d-m-Y'),
                    'quantity' => $batch->quantity,
                    'initial_quantity' => $batch->initial_quantity,
                    'status' => $batch->status,
                    'status_text' => $this->getBatchStatusText($batch->status),
                    'percentage_remaining' => $batch->initial_quantity > 0 
                        ? round(($batch->quantity / $batch->initial_quantity) * 100, 2)
                        : 0,
                    'is_expired' => $batch->isExpired(),
                    'days_to_expiry' => now()->diffInDays($batch->expiry_date, false)
                ];
            });
    }
    
    /**
     * Get active batches only (for sales/driver)
     */
    public function getAvailableBatchesAttribute()
    {
        return $this->batches()
            ->where('status', 1)
            ->where('quantity', '>', 0)
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date', 'asc') // FEFO - First Expiry First Out
            ->get()
            ->map(function($batch) {
                return [
                    'id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'expiry_date' => $batch->expiry_date->format('d-m-Y'),
                    'available_quantity' => $batch->quantity,
                    'days_to_expiry' => now()->diffInDays($batch->expiry_date, false)
                ];
            });
    }
    
    /**
     * Get batch status text
     */
    private function getBatchStatusText($status)
    {
        switch($status) {
            case 1: return 'Active';
            case 2: return 'Expired';
            case 3: return 'Depleted';
            default: return 'Unknown';
        }
    }
    
    /**
     * Check if product has enough stock for requested quantity
     */
    public function hasEnoughStock($requestedQuantity)
    {
        return $this->total_quantity >= $requestedQuantity;
    }
    
    /**
     * Get recommended batch to use for sales (FEFO)
     */
    public function getRecommendedBatch($quantity)
    {
        return $this->batches()
            ->where('status', 1)
            ->where('quantity', '>', 0)
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date', 'asc')
            ->where('quantity', '>=', $quantity)
            ->first();
    }
    
    /**
     * Get batches that will be used for a specific quantity (FEFO allocation)
     */
    public function allocateBatches($requestedQuantity)
    {
        $allocations = [];
        $remainingQty = $requestedQuantity;
        
        $batches = $this->batches()
            ->where('status', 1)
            ->where('quantity', '>', 0)
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date', 'asc')
            ->get();
        
        foreach ($batches as $batch) {
            if ($remainingQty <= 0) break;
            
            $takeQuantity = min($batch->quantity, $remainingQty);
            $allocations[] = [
                'batch_id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'expiry_date' => $batch->expiry_date->format('d-m-Y'),
                'quantity_to_take' => $takeQuantity,
                'remaining_in_batch' => $batch->quantity - $takeQuantity
            ];
            
            $remainingQty -= $takeQuantity;
        }
        
        return [
            'success' => ($remainingQty == 0),
            'requested' => $requestedQuantity,
            'allocated' => $requestedQuantity - $remainingQty,
            'allocations' => $allocations,
            'shortage' => $remainingQty > 0 ? $remainingQty : 0
        ];
    }
    
    /**
     * =============================================
     * CARTON CONVERSION HELPERS
     * =============================================
     */
    
    /**
     * Convert units to cartons for display
     */
    public function convertToCartons($units)
    {
        if (!$this->carton_enabled || !$this->units_per_carton) {
            return [
                'cartons' => 0,
                'loose_units' => $units,
                'display' => $units . ' units'
            ];
        }
        
        $cartons = floor($units / $this->units_per_carton);
        $looseUnits = $units % $this->units_per_carton;
        
        $display = '';
        if ($cartons > 0) {
            $display .= $cartons . ' carton(s)';
        }
        if ($looseUnits > 0) {
            $display .= ($cartons > 0 ? ' + ' : '') . $looseUnits . ' unit(s)';
        }
        
        return [
            'cartons' => $cartons,
            'loose_units' => $looseUnits,
            'display' => $display
        ];
    }
    
    /**
     * Get total quantity in carton format
     */
    public function getTotalQuantityInCartonsAttribute()
    {
        return $this->convertToCartons($this->total_quantity);
    }
    
    /**
     * =============================================
     * PROFITABILITY HELPERS
     * =============================================
     */
    
    /**
     * Get profit margin
     */
    public function getProfitMarginAttribute()
    {
        if (!$this->cost || $this->cost == 0) {
            return null;
        }
        return (($this->price - $this->cost) / $this->price) * 100;
    }
    
    /**
     * Get profit amount
     */
    public function getProfitAmountAttribute()
    {
        if (!$this->cost) {
            return null;
        }
        return $this->price - $this->cost;
    }

    /**
     * Get current cost
     */
    public function getCurrentCostAttribute()
    {
        return $this->cost;
    }

    /**
     * Get cost at specific date
     */
    public function getCostAtDate($date)
    {
        $costAtDate = ProductCost::where('product_id', $this->id)
            ->where('created_at', '<=', $date)
            ->orderBy('created_at', 'desc')
            ->first();
        
        return $costAtDate ? $costAtDate->cost : $this->cost;
    }
    
    /**
     * =============================================
     * INVENTORY SUMMARY FOR DRIVER/STAFF VIEW
     * =============================================
     */
    
    /**
     * Get complete inventory summary for dashboard
     * Now correctly using batches for current stock
     */
    public function getInventorySummaryAttribute()
    {
        return [
            'total_quantity' => $this->total_quantity,
            'total_quantity_display' => $this->carton_enabled 
                ? $this->total_quantity_in_cartons['display']
                : $this->total_quantity . ' units',
            'active_batches' => $this->active_batches_count,
            'total_batches' => $this->total_batches_count,
            'total_stock_in' => $this->total_stock_in, // Historical from transactions
            'total_stock_out' => $this->total_stock_out, // Historical from transactions
            'current_stock_value' => $this->current_stock_value,
            'expiring_soon' => $this->expiring_soon_quantity,
            'expired' => $this->expired_quantity,
            'depleted_batches' => $this->depleted_batches_count,
            'batches' => $this->batch_summary,
            'available_batches' => $this->available_batches,
            'stock_balance_check' => $this->total_stock_in - $this->total_stock_out, // Should equal total_quantity
            'is_balanced' => ($this->total_stock_in - $this->total_stock_out) == $this->total_quantity
        ];
    }
    
    /**
     * Verify stock balance (transactions vs batches)
     * Useful for auditing
     */
    public function verifyStockBalance()
    {
        $fromTransactions = $this->total_stock_in - $this->total_stock_out;
        $fromBatches = $this->total_quantity;
        
        return [
            'from_transactions' => $fromTransactions,
            'from_batches' => $fromBatches,
            'match' => $fromTransactions == $fromBatches,
            'difference' => $fromTransactions - $fromBatches
        ];
    }
}