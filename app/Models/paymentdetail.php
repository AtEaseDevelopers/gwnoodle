<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Class paymentdetail
 * @package App\Models
 * @version September 23, 2022, 3:36 pm UTC
 *
 * @property \App\Models\Driver $driver
 * @property integer $driver_id
 * @property string|\Carbon\Carbon $datefrom
 * @property string|\Carbon\Carbon $dateto
 * @property integer $month
 * @property number $do_amount
 * @property string $do_list
 * @property number $do_report
 * @property number $claim_amount
 * @property string $claim_list
 * @property number $do_report
 * @property number $comp_amount
 * @property string $comp_list
 * @property number $domp_report
 * @property number $adv_amount
 * @property string $adv_list
 * @property number $adv_report
 * @property number $loanpay_amount
 * @property string $loanpay_list
 * @property number $loanpay_report
 * @property number $bonus_amount
 * @property string $bonus_list
 * @property number $bonus_report
 * @property number $deduct_amount
 * @property number $final_amount
 * @property integer $status
 */
class paymentdetail extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'paymentdetails';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'driver_id',
        'datefrom',
        'dateto',
        'month',
        'do_amount',
        'do_list',
        'do_report',
        'claim_amount',
        'claim_list',
        'claim_report',
        'comp_amount',
        'comp_list',
        'comp_report',
        'adv_amount',
        'adv_list',
        'adv_report',
        'loanpay_amount',
        'loanpay_list',
        'loanpay_report',
        'bonus_amount',
        'bonus_list',
        'bonus_report',
        'deduct_amount',
        'final_amount',
        'status'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'driver_id' => 'integer',
        'datefrom' => 'date:d-m-Y',
        'dateto' => 'date:d-m-Y',
        'month' => 'integer',
        'do_amount' => 'float',
        'do_list' => 'string',
        'do_report' => 'integer',
        'claim_amount' => 'float',
        'claim_list' => 'string',
        'claim_report' => 'integer',
        'comp_amount' => 'float',
        'comp_list' => 'string',
        'comp_report' => 'integer',
        'adv_amount' => 'float',
        'adv_list' => 'string',
        'adv_report' => 'integer',
        'loanpay_amount' => 'float',
        'loanpay_list' => 'string',
        'loanpay_report' => 'integer',
        'bonus_amount' => 'float',
        'bonus_list' => 'string',
        'bonus_report' => 'integer',
        'deduct_amount' => 'float',
        'final_amount' => 'float',
        'status' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'driver_id' => 'required',
        'datefrom' => 'required',
        'dateto' => 'required',
        'month' => 'required',
        'do_amount' => 'nullable|numeric',
        'do_list' => 'nullable|string',
        'do_report' => 'nullable|numeric',
        'claim_amount' => 'nullable|numeric',
        'claim_list' => 'nullable|string',
        'claim_report' => 'nullable|numeric',
        'comp_amount' => 'nullable|numeric',
        'comp_list' => 'nullable|string',
        'comp_report' => 'nullable|numeric',
        'adv_amount' => 'nullable|numeric',
        'adv_list' => 'nullable|string',
        'adv_report' => 'nullable|numeric',
        'loanpay_amount' => 'nullable|numeric',
        'loanpay_list' => 'nullable|string',
        'loanpay_report' => 'nullable|numeric',
        'bonus_amount' => 'nullable|numeric',
        'bonus_list' => 'nullable|string',
        'bonus_report' => 'nullable|numeric',
        'deduct_amount' => 'nullable|numeric',
        'final_amount' => 'nullable|numeric',
        'status' => 'required',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id');
    }

    public function getDateFromAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    public function getDateToAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }
}
