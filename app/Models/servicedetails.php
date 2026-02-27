<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Class servicedetails
 * @package App\Models
 * @version February 4, 2023, 2:12 am +08
 *
 * @property \App\Models\Lorry $lorry
 * @property integer $lorry_id
 * @property string $type
 * @property string|\Carbon\Carbon $date
 * @property string|\Carbon\Carbon $nextdate
 * @property number $amount
 * @property string $remark
 */
class servicedetails extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'servicedetails';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'lorry_id',
        'type',
        'date',
        'nextdate',
        'amount',
        'remark'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'lorry_id' => 'integer',
        'type' => 'string',
        'date' => 'date:d-m-Y',
        'nextdate' => 'date:d-m-Y',
        'amount' => 'float',
        'remark' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'lorry_id' => 'required',
        'type' => 'required|string|max:255',
        'date' => 'required',
        'nextdate' => 'required|after:date',
        'amount' => 'nullable|numeric',
        'remark' => 'nullable|string|max:255',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

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

    public function getNextdateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }
}
