<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryRequest extends Model
{
    use HasFactory;

    public $table = 'inventory_requests'; 

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PARTIALLY_APPROVED = 'partially_approved';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'driver_id',
        'items', // JSON field storing array of items with product_id, quantity, and optionally batch allocations
        'status',
        'approved_by',
        'rejected_by',
        'rejection_reason',
        'approved_at',
        'rejected_at',
        'trip_id',
        'remarks', 
    ];

    protected $casts = [
        'id' => 'integer',
        'driver_id' => 'integer',
        'items' => 'array', // Cast items to array
        'status' => 'string',
        'rejection_reason' => 'string',
        'remarks' => 'string',
        'approved_by' => 'integer',
        'rejected_by' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime'
    ];

    public static $rules = [
        'driver_id' => 'required|exists:drivers,id',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
        'rejection_reason' => 'nullable|string|required_if:status,rejected',
        'remarks' => 'nullable|string|max:500'
    ];

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id', 'id');
    }

    public function products()
    {
        $productIds = collect($this->items)->pluck('product_id')->toArray();
        return \App\Models\Product::whereIn('id', $productIds);
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by', 'id');
    }

    public function rejector()
    {
        return $this->belongsTo(\App\Models\User::class, 'rejected_by', 'id');
    }

    /**
     * Get items with product details and batch recommendations
     */
    public function getItemsWithDetailsAttribute()
    {
        if (!$this->items) {
            return [];
        }

        $itemsWithDetails = [];
        
        foreach ($this->items as $index => $item) {
            $product = Product::find($item['product_id']);
            
            if (!$product) {
                continue;
            }

            // Get available batches for this product (FEFO order)
            $availableBatches = ProductBatch::where('product_id', $item['product_id'])
                ->where('status', ProductBatch::STATUS_ACTIVE)
                ->where('quantity', '>', 0)
                ->where('expiry_date', '>', now())
                ->orderBy('expiry_date', 'asc')
                ->get();

            // Check if there are saved batch allocations
            $savedAllocations = $item['batch_allocations'] ?? [];
            
            // Calculate total allocated quantity from saved allocations
            $allocatedQuantity = collect($savedAllocations)->sum('quantity');
            
            // Check if fully allocated
            $isFullyAllocated = $allocatedQuantity >= $item['quantity'];

            $itemsWithDetails[] = [
                'index' => $index,
                'product_id' => $item['product_id'],
                'product_name' => $product->name,
                'unit_code' => $product->unit_code,
                'requested_quantity' => $item['quantity'],
                'allocated_quantity' => $allocatedQuantity,
                'remaining_to_allocate' => $item['quantity'] - $allocatedQuantity,
                'is_fully_allocated' => $isFullyAllocated,
                'batch_allocations' => $savedAllocations,
                'available_batches' => $availableBatches->map(function($batch) use ($savedAllocations) {
                    // Find if this batch has a saved allocation
                    $savedAllocation = collect($savedAllocations)->firstWhere('batch_id', $batch->id);
                    
                    return [
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'available_quantity' => $batch->quantity,
                        'expiry_date' => $batch->formatted_expiry_date,
                        'days_to_expiry' => $batch->days_to_expiry,
                        'is_expiring_soon' => $batch->isExpiringSoon(),
                        'allocated_quantity' => $savedAllocation['quantity'] ?? 0,
                        'can_allocate' => $batch->quantity > 0
                    ];
                })->values()->toArray()
            ];
        }

        return $itemsWithDetails;
    }

    /**
     * Check if request is fully allocated
     */
    public function isFullyAllocated()
    {
        if (!$this->items) {
            return false;
        }

        foreach ($this->items as $item) {
            $allocatedQuantity = collect($item['batch_allocations'] ?? [])->sum('quantity');
            if ($allocatedQuantity < $item['quantity']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get batch allocations for all items
     */
    public function getAllBatchAllocationsAttribute()
    {
        if (!$this->items) {
            return [];
        }

        $allocations = [];
        foreach ($this->items as $item) {
            if (isset($item['batch_allocations'])) {
                foreach ($item['batch_allocations'] as $allocation) {
                    $batch = ProductBatch::with('product')->find($allocation['batch_id']);
                    if ($batch) {
                        $allocations[] = [
                            'batch_id' => $batch->id,
                            'batch_code' => $batch->batch_code,
                            'product_name' => $batch->product->name ?? 'Unknown',
                            'quantity' => $allocation['quantity'],
                            'expiry_date' => $batch->formatted_expiry_date
                        ];
                    }
                }
            }
        }

        return $allocations;
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope for partially approved requests
     */
    public function scopePartiallyApproved($query)
    {
        return $query->where('status', self::STATUS_PARTIALLY_APPROVED);
    }

    /**
     * Check if request can be updated
     */
    public function canBeUpdated()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PARTIALLY_APPROVED]);
    }

    /**
     * Check if request can be approved
     */
    public function canBeApproved()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PARTIALLY_APPROVED]);
    }

    /**
     * Check if request can be rejected
     */
    public function canBeRejected()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PARTIALLY_APPROVED]);
    }
    
    /**
     * Get total quantity of all items
     */
    public function getTotalQuantityAttribute()
    {
        if (!$this->items) return 0;
        return collect($this->items)->sum('quantity');
    }
    
    /**
     * Get item count
     */
    public function getItemCountAttribute()
    {
        if (!$this->items) return 0;
        return count($this->items);
    }

    /**
     * Get product names with quantities
     */
    public function getProductSummaryAttribute()
    {
        if (!$this->items) return '';
        
        $productNames = [];
        foreach ($this->items as $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $productNames[] = $product->name . ' (x' . $item['quantity'] . ')';
            }
        }
        return implode(', ', $productNames);
    }

    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_PARTIALLY_APPROVED => 'Partially Approved',
        ];
    }
    
}