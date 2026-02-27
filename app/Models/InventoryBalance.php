<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class InventoryBalance extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'inventory_balances';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';



    public $fillable = [
        'lorry_id',
        'product_id',
        'quantity'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'lorry_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'lorry_id' => 'required',
        'product_id' => 'required',
        'quantity' => 'required',
        'created_at' => 'nullable|nullable',
        'updated_at' => 'nullable|nullable'
    ];

    public function lorry()
    {
        return $this->belongsTo(\App\Models\Lorry::class, 'lorry_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id', 'id');
    }


}
