<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Location
 * @package App\Models
 * @version July 23, 2022, 4:23 pm UTC
 *
 * @property string $code
 * @property string $name
 * @property integer $source
 * @property integer $destination
 * @property string $phone
 * @property string $address1
 * @property string $address2
 * @property string $address3
 * @property string $address4
 * @property integer $status
 * @property string $remark
 * @property string $STR_UDF1
 * @property string $STR_UDF2
 * @property string $STR_UDF3
 * @property integer $INT_UDF1
 * @property integer $INT_UDF2
 * @property integer $INT_UDF3
 */
class Location extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'locations';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'code',
        'name',
        'source',
        'destination',
        'phone',
        'address1',
        'address2',
        'address3',
        'address4',
        'status',
        'remark',
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
        'code' => 'string',
        'name' => 'string',
        'source' => 'integer',
        'destination' => 'integer',
        'phone' => 'string',
        'address1' => 'string',
        'address2' => 'string',
        'address3' => 'string',
        'address4' => 'string',
        'status' => 'integer',
        'remark' => 'string',
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
        'code' => 'required|string|max:255|unique:locations,code',
        'name' => 'required|string|max:255',
        'source' => 'required',
        'destination' => 'required',
        'phone' => 'nullable|string|max:255',
        'address1' => 'nullable|string',
        'address2' => 'nullable|string',
        'address3' => 'nullable|string',
        'address4' => 'nullable|string',
        'status' => 'required',
        'remark' => 'nullable|string|max:255',
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

    
}
