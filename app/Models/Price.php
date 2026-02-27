<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Price
 * @package App\Models
 * @version August 2, 2022, 2:08 pm UTC
 *
 * @property \App\Models\Location $destinate
 * @property \App\Models\Location $source
 * @property \App\Models\Item $item
 * @property \App\Models\Vendor $vendor
 * @property integer $item_id
 * @property integer $vendor_id
 * @property integer $source_id
 * @property integer $destinate_id
 * @property number $minrange
 * @property number $maxrange
 * @property number $billingrate
 * @property number $commissionrate
 * @property integer $status
 * @property string $remark
 * @property string $STR_UDF1
 * @property string $STR_UDF2
 * @property string $STR_UDF3
 * @property integer $INT_UDF1
 * @property integer $INT_UDF2
 * @property integer $INT_UDF3
 */
class Price extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'prices';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'item_id',
        'vendor_id',
        'source_id',
        'destinate_id',
        'minrange',
        'maxrange',
        'billingrate',
        'commissionrate',
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
        'item_id' => 'integer',
        'vendor_id' => 'integer',
        'source_id' => 'integer',
        'destinate_id' => 'integer',
        'minrange' => 'float',
        'maxrange' => 'float',
        'billingrate' => 'float',
        'commissionrate' => 'float',
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
        'item_id' => 'required',
        'vendor_id' => 'required',
        'source_id' => 'required',
        'destinate_id' => 'required',
        'minrange' => 'required|numeric|min:0',
        'maxrange' => 'required|numeric|gte:minrange|min:0',
        'billingrate' => 'required|numeric|min:0',
        'commissionrate' => 'required|numeric|min:0',
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
    public function destinate()
    {
        return $this->belongsTo(\App\Models\Location::class, 'destinate_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function source()
    {
        return $this->belongsTo(\App\Models\Location::class, 'source_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function item()
    {
        return $this->belongsTo(\App\Models\Item::class, 'item_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function vendor()
    {
        return $this->belongsTo(\App\Models\Vendor::class, 'vendor_id');
    }
}
