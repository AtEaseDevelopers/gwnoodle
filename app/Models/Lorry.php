<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Lorry
 * @package App\Models
 * @version July 23, 2022, 11:31 am UTC
 *
 * @property string $lorryno
 * @property string $type
 * @property number $weightagelimit
 * @property number $commissionlimit
 * @property number $commissionpercentage
 * @property string $permitholder
 * @property integer $status
 * @property string $remark
 * @property string $STR_UDF1
 * @property string $STR_UDF2
 * @property string $STR_UDF3
 * @property integer $INT_UDF1
 * @property integer $INT_UDF2
 * @property integer $INT_UDF3
 */
class Lorry extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'lorrys';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'lorryno',
        'status',
        'remark'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'lorryno' => 'string',
        'status' => 'integer',
        'remark' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'lorryno' => 'required|string|max:255|unique:lorrys,lorryno',
        'status' => 'required',
        'remark' => 'nullable|string|max:255'
    ];

    // /**
    //  * @return \Illuminate\Database\Eloquent\Relations\HasMany
    //  **/
    // public function servicedetails()
    // {
    //     return $this->hasMany(\App\Models\servicedetails::class, 'lorry_id');
    // }

    
}
