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
        'payment_batch_id',
        'type',
        'customer_id',
        'amount',
        'status',
        'autocount_status',
        'autocount_message',
        'payment_no',
        'doc_id',
        'autocount_auto_retried',
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
    // AutoCount AR Payment sync states (autocount_status column).
    const AC_PENDING = 'pending'; // queued for auto sync (non-credit)
    const AC_HOLD    = 'hold';    // credit - waits for a manual web "Sync" click
    const AC_SUCCESS = 'success';
    const AC_FAILED  = 'failed';
    const AC_SKIPPED = 'skipped';

    protected $casts = [
        'id' => 'integer',
        'invoice_id' => 'integer',
        'type' => 'integer',
        'customer_id' => 'integer',
        'amount' => 'float',
        'status' => 'integer',
        'doc_id' => 'integer',
        'autocount_auto_retried' => 'boolean',
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

    /**
     * Serialize dates in the app timezone (MYT) instead of UTC.
     *
     * created_at is a TIMESTAMP column AND carries a `date:d-m-Y` cast. When a
     * model is arrayed/JSONed (e.g. by the DataTable), Laravel first runs every
     * getDates() attribute through serializeDate(), whose default collapses the
     * Carbon to its true UTC instant (toJSON). The `date:d-m-Y` cast then reparses
     * that UTC string, so a payment made at 05:28 MYT (21:28 UTC the day before)
     * rendered as the previous day. Formatting in local time keeps the calendar
     * date the user actually recorded.
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
