<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class Invoice extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'invoices';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public const STATUS_SYNCED_TO_XERO = 1;
    public const STATUS_VOIDED = 2;

    const STATUS_COMPLETED = 1;
    const STATUS_NEW = 2;

    public $fillable = [
        'invoiceno',
        'date',
        'customer_id',
        'driver_id',
        'default_driver_id',
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
        'default_driver_id' => 'integer',
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
        'driver_id' => 'nullable|integer|exists:drivers,id',
        'default_driver_id' => 'nullable|integer|exists:drivers,id',
        'status' => 'required',
        'remark' => 'nullable|string|max:255|string|max:255',
        'created_at' => 'nullable|nullable',
        'updated_at' => 'nullable|nullable'
    ];

    public function getStatusTextAttribute()
    {
        return $this->status = 1 ? 'Completed' : 'New';
    }
    
    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id', 'id');
    }

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id', 'id');
    }

    public function defaultDriver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'default_driver_id', 'id');
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


      public static function generateInvoiceNumber($driver_id = null)
    {
        // Get current year and month
        $year = date('y'); // Last 2 digits of year
        $month = date('m'); // Month with leading zeros
        
        if ($driver_id) {
            $user = \App\Models\Driver::find($driver_id);
        } else {
            $user = Auth::user();
        }

        $userCode = $user->invoice_code ?? ''; // Default to R00 if not set
        
        // Get the latest invoice number for current month and user code
        $prefix = "INV{$year}{$month}/{$userCode}/";
        
        // Find the latest invoice with this prefix
        $latestInvoice = self::orderBy('id', 'desc')
            ->first();
        if ($latestInvoice) {
            // Extract the numeric part
            $invoiceNumber = $latestInvoice->invoiceno;
            $numericPart = (int) substr($invoiceNumber, strlen($prefix));
            $nextNumber = $numericPart + 1;
        } else {
            // Start from 1 for new month/user combination
            $nextNumber = 1;
        }
        
        // Format the number with leading zeros (minimum 4 digits, but can grow)
        $formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        
        return $prefix . $formattedNumber;
    }

    public static function getPaymentTypeOptions()
    {
        return [
            self::PAYMENT_TYPE_CASH => 'Cash',
            self::PAYMENT_TYPE_CREDIT => 'Credit',
        ];
    }


    /**
     * Get the next invoice number without saving
     * This can be used to prefill the form
     *
     * @return string
     */
    public static function getNextInvoiceNumber($driver_id = null)
    {
        return self::generateInvoiceNumber($driver_id);
    }

    public static function invoiceNumberExists($invoiceNumber)
    {
        return self::where('invoiceno', $invoiceNumber)->exists();
    }

}
