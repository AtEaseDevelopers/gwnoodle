<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseInventoryBalance extends Model
{
    use HasFactory;

    protected $table = 'warehouse_inventory_balances';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'batch_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    /**
     * Increase quantity for a batch
     */
    public function increaseQuantity($amount)
    {
        $this->quantity += $amount;
        return $this->save();
    }

    /**
     * Decrease quantity for a batch
     */
    public function decreaseQuantity($amount)
    {
        if ($this->quantity < $amount) {
            throw new \Exception("Insufficient quantity. Available: {$this->quantity}, Requested: {$amount}");
        }
        
        $this->quantity -= $amount;
        return $this->save();
    }

    /**
     * Get total quantity for a product across all batches in a warehouse
     */
    public static function getProductTotalQuantity($warehouseId, $productId)
    {
        return self::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->sum('quantity');
    }
}