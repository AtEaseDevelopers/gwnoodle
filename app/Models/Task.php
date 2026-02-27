<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class Task extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'tasks';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';



    public $fillable = [
        'date',
        'trip_id',
        'driver_id',
        'customer_id',
        'sequence',
        'invoice_id',
        'status',
        'based'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'date' => 'date:d-m-Y',
        'driver_id' => 'integer',
        'customer_id' => 'integer',
        'sequence' => 'integer',
        'invoice_id' => 'integer',
        'status' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'date' => 'required',
        'driver_id' => 'required',
        'customer_id' => 'required',
        'sequence' => 'required',
        'invoice_id' => 'nullable',
        'status' => 'required',
        'created_at' => 'nullable',
        'updated_at' => 'nullable'
    ];

    public function invoice()
    {
        return $this->belongsTo(\App\Models\Invoice::class, 'invoice_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id', 'id');
    }

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id', 'id');
    }

    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }


}
