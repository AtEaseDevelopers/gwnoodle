<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Class Loan
 * @package App\Models
 * @version August 5, 2022, 2:47 pm UTC
 *
 * @property \App\Models\Driver $driver
 * @property \Illuminate\Database\Eloquent\Collection $loanpayments
 * @property string|\Carbon\Carbon $date
 * @property integer $driver_id
 * @property string $description
 * @property number $amount
 * @property integer $period
 * @property number $rate
 * @property number $totalamount
 * @property number $monthlyamount
 * @property integer $status
 * @property string $STR_UDF1
 * @property string $STR_UDF2
 * @property string $STR_UDF3
 * @property integer $INT_UDF1
 * @property integer $INT_UDF2
 * @property integer $INT_UDF3
 * @property string $outstanding
 */
class Loan extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'loans';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'date',
        'driver_id',
        'description',
        'amount',
        'period',
        'rate',
        'totalamount',
        'monthlyamount',
        'status',
        'STR_UDF1',
        'STR_UDF2',
        'STR_UDF3',
        'INT_UDF1',
        'INT_UDF2',
        'INT_UDF3'
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
        'description' => 'string',
        'amount' => 'float',
        'period' => 'integer',
        'rate' => 'float',
        'totalamount' => 'float',
        'monthlyamount' => 'float',
        'status' => 'integer',
        'STR_UDF1' => 'string',
        'STR_UDF2' => 'string',
        'STR_UDF3' => 'string',
        'INT_UDF1' => 'integer',
        'INT_UDF2' => 'integer',
        'INT_UDF3' => 'integer',
        'totalinterest' => 'float'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'date' => 'required',
        'driver_id' => 'required',
        'description' => 'required|string',
        'amount' => 'required|numeric',
        'period' => 'required|integer',
        'rate' => 'required|numeric',
        // 'totalamount' => 'required|numeric',
        // 'monthlyamount' => 'required|numeric',
        // 'status' => 'required',
        'STR_UDF1' => 'nullable|string',
        'STR_UDF2' => 'nullable|string',
        'STR_UDF3' => 'nullable|string',
        'INT_UDF1' => 'nullable|integer',
        'INT_UDF2' => 'nullable|integer',
        'INT_UDF3' => 'nullable|integer',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    public function getLoanOutstanding()
    {
        $loanpayments = $this->loanpayments;
        $paidamount = 0;
        $loanoutstanding = 0;
        foreach($loanpayments as $loanpayment){
            if($loanpayment->payment == 1){
                $paidamount = $paidamount + $loanpayment->amount;
            }
        }
        $loanoutstanding = $this->totalamount - $paidamount;
        return $loanoutstanding;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function loanpayments()
    {
        return $this->hasMany(\App\Models\Loanpayment::class, 'loan_id');
    }

    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }   
}
