<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    use HasFactory;

    protected $table = 'product_batches';

    protected $fillable = [
        'product_id',
        'batch_code',
        'manufacturing_date',
        'expiry_date',
        'quantity',
        'initial_quantity',   
        'qr_code',
        'barcode_data',
        'status', // 1=active, 2=expired, 3=depleted
    ];

    protected $casts = [
        'product_id' => 'integer',
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'quantity' => 'integer',
        'status' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'batch_id');
    }

    /**
     * Check if batch is expired
     */
    public function isExpired()
    {
        return $this->expiry_date < now();
    }

    /**
     * Check if batch has stock
     */
    public function hasStock()
    {
        return $this->quantity > 0;
    }
}