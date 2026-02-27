<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class CommissionByVendors
 * @package App\Models
 * @version August 30, 2022, 1:49 pm UTC
 *
 * @property \App\Models\Lorry $lorry
 * @property \App\Models\Vendor $vendor
 * @property integer $lorry_id
 * @property integer $vendor_id
 * @property string $description
 * @property number $commissionlimit
 * @property number $commissionpercentage
 * @property integer $status
 * @property string $remark
 * @property string $STR_UDF1
 * @property string $STR_UDF2
 * @property string $STR_UDF3
 * @property integer $INT_UDF1
 * @property integer $INT_UDF2
 * @property integer $INT_UDF3
 */
class CommissionByVendors extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'commissionbyvendors';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'lorry_id',
        'vendor_id',
        'description',
        'commissionlimit',
        'commissionpercentage',
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
        'lorry_id' => 'integer',
        'vendor_id' => 'integer',
        'description' => 'string',
        'commissionlimit' => 'float',
        'commissionpercentage' => 'float',
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
        'lorry_id' => 'required',
        'vendor_id' => 'required',
        'description' => 'nullable|string|max:255',
        'commissionlimit' => 'required|numeric',
        'commissionpercentage' => 'required|numeric',
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function lorry()
    {
        return $this->belongsTo(\App\Models\Lorry::class, 'lorry_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function vendor()
    {
        return $this->belongsTo(\App\Models\Vendor::class, 'vendor_id');
    }
}
