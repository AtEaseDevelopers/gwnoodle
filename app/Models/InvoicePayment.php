<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class InvoicePayment extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'invoice_payments';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';



    public $fillable = [
        'invoice_id',
        'type',
        'customer_id',
        'amount',
        'status',
        'attachment',
        'driver_id',
        'user_id',
        'approve_by',
        'approve_at',
        'remark',
        'chequeno'
    ];

    protected $attributes = [
        'status' => 0
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'invoice_id' => 'integer',
        'type' => 'integer',
        'customer_id' => 'integer',
        'amount' => 'float',
        'status' => 'integer',
        'attachment' => 'string',
        'driver_id' => 'integer',
        'user_id' => 'integer',
        'approve_by' => 'string',
        'approve_at' => 'date:d-m-Y',
        'created_at' => 'date:d-m-Y',
        'remark' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'invoice_id' => 'nullable',
        'type' => 'required',
        'customer_id' => 'required',
        'amount' => 'required|numeric|numeric',
        'status' => 'nullable',
        // 'attachment' => 'nullable|string|max:65535',
        'approve_by' => 'nullable|string|max:255',
        'approve_at' => 'nullable',
        'remark' => 'nullable|string|max:255',
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

    public function getApproveAtAttribute($value)
    {
        if($value == ''){
            return "";
        }
        return Carbon::parse($value)->format('d-m-Y');
    }

    
}
