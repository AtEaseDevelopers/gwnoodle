<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Class Bonus
 * @package App\Models
 * @version August 3, 2022, 8:42 am UTC
 *
 * @property \App\Models\Location $destinate
 * @property \App\Models\Vendor $vendor
 * @property \App\Models\Location $source
 * @property string $name
 * @property integer $vendor_id
 * @property integer $source_id
 * @property integer $destinate_id
 * @property number $target
 * @property string|\Carbon\Carbon $bonusstart
 * @property string|\Carbon\Carbon $bonusend
 * @property number $amount
 * @property integer $status
 * @property string $STR_UDF1
 * @property string $STR_UDF2
 * @property string $STR_UDF3
 * @property integer $INT_UDF1
 * @property integer $INT_UDF2
 * @property integer $INT_UDF3
 */
class Bonus extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'bonus';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'name',
        'vendor_id',
        'source_id',
        'destinate_id',
        'target',
        'bonusstart',
        'bonusend',
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
        'name' => 'string',
        'vendor_id' => 'integer',
        'source_id' => 'integer',
        'destinate_id' => 'integer',
        'target' => 'float',
        'bonusstart' => 'date:d-m-Y',
        'bonusend' => 'date:d-m-Y',
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
        'name' => 'required|string|max:255',
        'vendor_id' => 'nullable',
        'source_id' => 'nullable',
        'destinate_id' => 'nullable',
        'target' => 'required|numeric|min:0',
        'bonusstart' => 'required',
        'bonusend' => 'required|after_or_equal:bonusstart',
        'amount' => 'required|numeric|min:0',
        'status' => 'required',
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
    public function destinate()
    {
        return $this->belongsTo(\App\Models\Location::class, 'destinate_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function vendor()
    {
        return $this->belongsTo(\App\Models\Vendor::class, 'vendor_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function source()
    {
        return $this->belongsTo(\App\Models\Location::class, 'source_id');
    }

    public function getBonusStartAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    public function getBonusEndAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }
}
