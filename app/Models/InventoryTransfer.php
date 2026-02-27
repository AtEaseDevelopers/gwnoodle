<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class InventoryTransfer extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'inventory_transfers';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';



    public $fillable = [
        'date',
        'from_driver_id',
        'from_lorry_id',
        'to_driver_id',
        'to_lorry_id',
        'product_id',
        'quantity',
        'status',
        'remark'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'date' => 'date:d-m-Y H:i:s',
        'from_driver_id' => 'integer',
        'from_lorry_id' => 'integer',
        'to_driver_id' => 'integer',
        'to_lorry_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'status' => 'integer',
        'remark' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'date' => 'required',
        'from_driver_id' => 'required',
        'from_lorry_id' => 'required',
        'to_driver_id' => 'required',
        'to_lorry_id' => 'required',
        'product_id' => 'required',
        'quantity' => 'required',
        'status' => 'required',
        'remark' => 'string|max:255|string|max:255',
        'created_at' => 'nullable|nullable',
        'updated_at' => 'nullable|nullable'
    ];

    public function fromdriver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'from_driver_id', 'id');
    }

    public function fromlorry()
    {
        return $this->belongsTo(\App\Models\Lorry::class, 'from_lorry_id', 'id');
    }

    public function todriver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'to_driver_id', 'id');
    }

    public function tolorry()
    {
        return $this->belongsTo(\App\Models\Lorry::class, 'to_lorry_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id', 'id');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y H:i:s');
    }


}
