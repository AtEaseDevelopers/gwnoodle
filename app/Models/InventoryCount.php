<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryCount extends Model
{
    use HasFactory;

    public $table = 'inventory_counts';

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'driver_id',
        'items', // Changed from product_id, quantity to JSON field
        'status',
        'approved_by',
        'rejected_by',
        'rejection_reason',
        'approved_at',
        'rejected_at',
        'trip_id',
        'remarks',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
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

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'driver_id' => 'required|exists:drivers,id',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
        'rejection_reason' => 'nullable|string|required_if:status,rejected',
        'remarks' => 'nullable|string|max:500'
    ];

    /**
     * Get the driver that owns the inventory count.
     */
    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id', 'id');
    }

    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }
    
    /**
     * Get the products through items relationship.
     * This is a many-to-many relationship using the items JSON field.
     */
    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            null, // No pivot table since we use JSON
            'inventory_count_id',
            'product_id'
        )->using(InventoryCountProduct::class)
         ->withPivot('quantity', 'name') // These are from your items array
         ->withTimestamps();
    }

    /**
     * Get related products using eager loading with custom logic
     * This method loads products based on the product_ids in the items array
     */
    public function getProductsAttribute()
    {
        $productIds = collect($this->items)->pluck('product_id')->toArray();
        return Product::whereIn('id', $productIds)->get();
    }

    /**
     * Get items with product information
     */
    public function getItemsWithProductsAttribute()
    {
        $items = $this->items ?? [];
        $productIds = collect($items)->pluck('product_id')->toArray();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        
        return collect($items)->map(function ($item) use ($products) {
            $product = $products[$item['product_id']] ?? null;
            return [
                'product_id' => $item['product_id'],
                'name' => $product ? $product->name : ($item['name'] ?? 'Unknown'),
                'current_quantity' => $item['current_quantity'] ?? 0,
                'counted_quantity' => $item['counted_quantity'] ?? 0,
                'code' => $product ? $product->code : null,
                // Add any other product fields you need
            ];
        });
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
     * Get total quantity of all items
     */
    public function getTotalQuantityAttribute()
    {
        return collect($this->items)->sum('quantity');
    }
    
    /**
     * Get item count
     */
    public function getItemCountAttribute()
    {
        return count($this->items);
    }

    /**
     * Get product names with quantities
     */
    public function getProductSummaryAttribute()
    {
        $productNames = [];
        foreach ($this->items as $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $productNames[] = $product->name . ' (x' . $item['quantity'] . ')';
            }
        }
        return implode(', ', $productNames);
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
     * Check if request can be updated
     */
    public function canBeUpdated()
    {
        return in_array($this->status, [self::STATUS_PENDING]);
    }

    /**
     * Check if request can be approved
     */
    public function canBeApproved()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if request can be rejected
     */
    public function canBeRejected()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}