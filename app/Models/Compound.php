<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Class Compound
 * @package App\Models
 * @version August 4, 2022, 2:53 pm UTC
 *
 * @property \App\Models\Driver $driver
 * @property \App\Models\Lorry $lorry
 * @property string|\Carbon\Carbon $date
 * @property string $no
 * @property integer $driver_id
 * @property integer $lorry_id
 * @property string $permitholder
 * @property string $description
 * @property number $amount
 * @property integer $status
 * @property string $STR_UDF1
 * @property string $STR_UDF2
 * @property string $STR_UDF3
 * @property integer $INT_UDF1
 * @property integer $INT_UDF2
 * @property integer $INT_UDF3
 */
class Compound extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'compounds';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'date',
        'no',
        'driver_id',
        'lorry_id',
        'permitholder',
        'description',
        'amount',
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
        'no' => 'string',
        'driver_id' => 'integer',
        'lorry_id' => 'integer',
        'permitholder' => 'string',
        'description' => 'string',
        'amount' => 'float',
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
        'date' => 'required',
        'no' => 'max:255|unique:compounds,no',
        // 'driver_id' => 'required',
        // 'lorry_id' => 'required',
        'permitholder' => 'nullable|string|max:255',
        'description' => 'required|string',
        'amount' => 'required|numeric|min:0',
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function lorry()
    {
        return $this->belongsTo(\App\Models\Lorry::class, 'lorry_id');
    }

    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }
}
