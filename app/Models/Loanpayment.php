<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Class Loanpayment
 * @package App\Models
 * @version August 5, 2022, 2:48 pm UTC
 *
 * @property \App\Models\Loan $loan
 * @property integer $loan_id
 * @property string|\Carbon\Carbon $date
 * @property string $description
 * @property number $amount
 * @property string $source
 * @property integer $payment
 * @property integer $status
 * @property string $STR_UDF1
 * @property string $STR_UDF2
 * @property string $STR_UDF3
 * @property integer $INT_UDF1
 * @property integer $INT_UDF2
 * @property integer $INT_UDF3
 */
class Loanpayment extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'loanpayments';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'loan_id',
        'date',
        'description',
        'amount',
        'source',
        'payment',
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
        'loan_id' => 'integer',
        'date' => 'date:d-m-Y',
        'description' => 'string',
        'amount' => 'float',
        'source' => 'string',
        'payment' => 'integer',
        'status' => 'integer',
        'STR_UDF1' => 'string',
        'STR_UDF2' => 'string',
        'STR_UDF3' => 'string',
        'INT_UDF1' => 'integer',
        'INT_UDF2' => 'integer',
        'INT_UDF3' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'loan_id' => 'required',
        'date' => 'required',
        'description' => 'required|string',
        'amount' => 'required|numeric',
        'source' => 'required|string|max:255',
        'payment' => 'required|integer',
        'status' => 'nullable',
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function loan()
    {
        return $this->belongsTo(\App\Models\Loan::class, 'loan_id');
    }

    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }
}
