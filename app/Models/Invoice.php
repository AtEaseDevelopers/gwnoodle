<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class Invoice extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'invoices';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public const STATUS_SYNCED_TO_XERO = 1;
    public const STATUS_VOIDED = 2;

    public $fillable = [
        'invoiceno',
        'date',
        'customer_id',
        'driver_id',
        'kelindan_id',
        'agent_id',
        'supervisor_id',
        'paymentterm',
        'status',
        'remark',
        'chequeno'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'invoiceno' => 'string',
        'date' => 'datetime:d-m-Y H:i:s',
        'customer_id' => 'integer',
        'driver_id' => 'integer',
        'kelindan_id' => 'integer',
        'agent_id' => 'integer',
        'supervisor_id' => 'integer',
        'paymentterm' => 'integer',
        'status' => 'integer',
        'remark' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'invoiceno' => 'nullable|string|max:255|string|max:255',
        'date' => 'required',
        'customer_id' => 'required',
        'paymentterm' => 'required',
        'status' => 'required',
        'remark' => 'nullable|string|max:255|string|max:255',
        'created_at' => 'nullable|nullable',
        'updated_at' => 'nullable|nullable'
    ];

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id', 'id');
    }

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id', 'id');
    }

    public function kelindan()
    {
        return $this->belongsTo(\App\Models\Kelindan::class, 'kelindan_id', 'id');
    }

    public function agent()
    {
        return $this->belongsTo(\App\Models\Agent::class, 'agent_id', 'id');
    }

    public function supervisor()
    {
        return $this->belongsTo(\App\Models\Supervisor::class, 'supervisor_id', 'id');
    }

    public function invoicedetail()
    {
        return $this->hasMany(\App\Models\InvoiceDetail::class);
    }

    public function invoicepayment()
    {
        return $this->hasMany(\App\Models\InvoicePayment::class);
    }

    public function einvoice()
    {
        return $this->hasOne(\App\Models\Einvoice::class, 'invoice_batch_id', 'id');
    }

    public function consolidatedEinvoices()
    {
        return $this->belongsToMany(\App\Models\ConsolidatedEinvoice::class, 'consolidated_einvoice_invoices', 'invoice_id', 'consolidated_einvoice_id');
    }

    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y H:i:s');
    }


}
