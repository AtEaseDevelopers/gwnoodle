<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class InventoryTransaction extends Model
{
    use HasFactory;

    public $table = 'inventory_transactions';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'lorry_id',
        'product_id',
        'batch_id',
        'quantity',
        'type',
        'remark',
        'date',
        'user',
        'qr_code_scanned',
        'expiry_date_from_scan',
        'reference_type',  // NEW: 'sales_order', 'sales_return', etc.
        'reference_id',     // NEW: ID of the sales order
        'customer_id'       // NEW: Direct customer reference for easy tracking
    ];

    protected $casts = [
        'id' => 'integer',
        'lorry_id' => 'integer',
        'product_id' => 'integer',
        'batch_id' => 'integer',
        'quantity' => 'integer',
        'type' => 'integer',
        'remark' => 'string',
        'date' => 'date:d-m-Y H:i:s',
        'user' => 'string',
        'expiry_date_from_scan' => 'date',
        'reference_id' => 'integer',
        'customer_id' => 'integer'
    ];

    public static $rules = [
        'lorry_id' => 'required',
        'product_id' => 'required',
        'batch_id' => 'nullable|integer',
        'quantity' => 'required',
        'type' => 'required',
        'remark' => 'string|max:255',
        'user' => 'string|max:255',
        'created_at' => 'nullable',
        'updated_at' => 'nullable'
    ];

    // Relationships
    public function lorry()
    {
        return $this->belongsTo(\App\Models\Lorry::class, 'lorry_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id', 'id');
    }

    public function batch()
    {
        return $this->belongsTo(\App\Models\ProductBatch::class, 'batch_id', 'id');
    }

    // NEW: Customer relationship
    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id', 'id');
    }

    // NEW: Polymorphic relationship to reference (sales order, return, etc.)
    public function reference()
    {
        return $this->morphTo();
    }
}