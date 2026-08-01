<?php

namespace App\Models;
use App\Traits\LogsActivity;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class InvoiceDetail extends Model
{
    use HasFactory;
    use LogsActivity;

    public $table = 'invoice_details';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'invoice_id',
        'product_id',
        'product_batch_id',
        'warehouse_id',
        'quantity',
        'price',
        'totalprice',
        'remark',
        'deducted_from_inventory'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'invoice_id' => 'integer',
        'product_id' => 'integer',
        'product_batch_id' => 'integer', // NEW: Cast to integer
        'quantity' => 'integer',
        'price' => 'float',
        'totalprice' => 'float',
        'remark' => 'string',
        'deducted_from_inventory' => 'boolean'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'invoice_id' => 'required|exists:invoices,id',
        'product_id' => 'required|exists:products,id',
        'product_batch_id' => 'required|exists:product_batches,id', // NEW: Validation rule
        'quantity' => 'required|integer|min:1',
        'price' => 'required|numeric|min:0',
        'remark' => 'nullable|string|max:255',
        'created_at' => 'nullable',
        'updated_at' => 'nullable'
    ];

    /**
     * Relationships
     */
    public function invoice()
    {
        return $this->belongsTo(\App\Models\Invoice::class, 'invoice_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id', 'id');
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'warehouse_id', 'id');
    }

    public function getLogIdentifier()
    {
        $invoiceno = $this->invoice->invoiceno ?? null;
        $productName = $this->product->name ?? null;

        if ($invoiceno && $productName) {
            return "{$invoiceno} - {$productName}";
        }

        return $invoiceno ?? "ID: {$this->getKey()}";
    }

    public function batch()
    {
        return $this->belongsTo(\App\Models\ProductBatch::class, 'product_batch_id', 'id');
    }

    /**
     * Accessors & Mutators
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2);
    }

    public function getFormattedTotalPriceAttribute()
    {
        return number_format($this->totalprice, 2);
    }

    public function getFormattedQuantityAttribute()
    {
        return number_format($this->quantity);
    }

    /**
     * Boot function to handle events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto-calculate total price if not set
            if (!$model->totalprice) {
                $model->totalprice = $model->quantity * $model->price;
            }
        });

        static::updating(function ($model) {
            // Recalculate total price if quantity or price changes
            if ($model->isDirty('quantity') || $model->isDirty('price')) {
                $model->totalprice = $model->quantity * $model->price;
            }
        });
    }

    /**
     * Scopes
     */
    public function scopeForInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForBatch($query, $batchId)
    {
        return $query->where('product_batch_id', $batchId);
    }
}