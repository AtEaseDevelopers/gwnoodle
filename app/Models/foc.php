<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class foc extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'focs';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $attributes = [
        'achievequantity' => 0,
    ];

    public $fillable = [
        'product_id',
        'customer_id',
        'achievequantity',
        'quantity',
        'free_product_id',
        'free_quantity',
        'startdate',
        'enddate',
        'status'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'product_id' => 'integer',
        'customer_id' => 'integer',
        'achievequantity' => 'integer',
        'quantity' => 'integer',
        'free_product_id' => 'integer',
        'free_quantity' => 'integer',
        'startdate' => 'date:d-m-Y',
        'enddate' => 'date:d-m-Y',
        'status' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'product_id' => 'required',
        'customer_id' => 'required',
        'quantity' => 'required|numeric|numeric',
        'free_product_id' => 'required',
        'free_quantity' => 'required|numeric|numeric',
        'startdate' => 'required',
        'enddate' => 'required',
        'status' => 'required',
        'created_at' => 'nullable|nullable',
        'updated_at' => 'nullable|nullable'
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id', 'id');
    }

    public function freeproduct()
    {
        return $this->belongsTo(\App\Models\Product::class, 'free_product_id', 'id');
    }

    public function getStartDateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    public function getEndDateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

}
