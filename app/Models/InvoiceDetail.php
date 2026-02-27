<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class InvoiceDetail extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'invoice_details';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';



    public $fillable = [
        'invoice_id',
        'product_id',
        'quantity',
        'price',
        'totalprice',
        'remark'
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
        'quantity' => 'integer',
        'price' => 'float',
        'remark' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'invoice_id' => 'required',
        'product_id' => 'required',
        'quantity' => 'required|integer|integer',
        'price' => 'required|numeric|numeric',
        'remark' => 'nullable|string|max:255|string|max:255',
        'created_at' => 'nullable|nullable',
        'updated_at' => 'nullable|nullable'
    ];

    public function invoice()
    {
        return $this->belongsTo(\App\Models\Invoice::class, 'invoice_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id', 'id');
    }
    
}
