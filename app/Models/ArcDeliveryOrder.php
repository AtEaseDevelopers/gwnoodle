<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Class DeliveryOrder
 * @package App\Models
 * @version August 13, 2022, 2:11 pm UTC
 *
 * @property \App\Models\Location $destinate
 * @property \App\Models\Item $item
 * @property \App\Models\Location $source
 * @property \App\Models\Driver $driver
 * @property \App\Models\Lorry $lorry
 * @property \App\Models\Vendor $vendor
 * @property string $dono
 * @property string|\Carbon\Carbon $date
 * @property integer $driver_id
 * @property integer $lorry_id
 * @property integer $vendor_id
 * @property integer $source_id
 * @property integer $destinate_id
 * @property integer $item_id
 * @property number $weight
 * @property number $shipweight
 * @property number $fees
 * @property number $tol
 * @property number $billingrate
 * @property number $commissionrate
 * @property integer $status
 * @property string $remark
 * @property integer $calstatus
 * @property string $STR_UDF1
 * @property string $STR_UDF2
 * @property string $STR_UDF3
 * @property integer $INT_UDF1
 * @property integer $INT_UDF2
 * @property integer $INT_UDF3
 * @property string|\Carbon\Carbon $archived_at
 */
class ArcDeliveryOrder extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'arc_deliveryorders';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'dono',
        'date',
        'driver_id',
        'lorry_id',
        'vendor_id',
        'source_id',
        'destinate_id',
        'item_id',
        'weight',
        'shipweight',
        'fees',
        'tol',
        'billingrate',
        'commissionrate',
        'status',
        'remark',
        'calstatus',
        'STR_UDF1',
        'STR_UDF2',
        'STR_UDF3',
        'INT_UDF1',
        'INT_UDF2',
        'INT_UDF3',
        'archived_at'
    ];

    protected $attributes = [
        'fees' => 0.00,
        'tol' => 0.00,
        'calstatus' => 1
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'dono' => 'string',
        'date' => 'date:d-m-Y',
        'driver_id' => 'integer',
        'lorry_id' => 'integer',
        'vendor_id' => 'integer',
        'source_id' => 'integer',
        'destinate_id' => 'integer',
        'item_id' => 'integer',
        'weight' => 'float',
        'shipweight' => 'float',
        'fees' => 'float',
        'tol' => 'float',
        'billingrate' => 'float',
        'commissionrate' => 'float',
        'status' => 'integer',
        'remark' => 'string',
        'calstatus;' => 'integer',
        'STR_UDF1' => 'string',
        'STR_UDF2' => 'string',
        'STR_UDF3' => 'string',
        'INT_UDF1' => 'integer',
        'INT_UDF2' => 'integer',
        'INT_UDF3' => 'integer',
        'archived_at' => 'date:d-m-Y'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'dono' => 'required|string|max:255|unique:deliveryorders,dono',
        'date' => 'required',
        'driver_id' => 'required',
        'lorry_id' => 'required',
        'vendor_id' => 'required',
        'source_id' => 'required',
        'destinate_id' => 'required',
        'item_id' => 'required',
        'weight' => 'required|numeric',
        'shipweight' => 'nullable|numeric',
        // 'billingrate' => 'required|numeric',
        // 'commissionrate' => 'required|numeric',
        'fees' => 'required|numeric',
        'tol' => 'required|numeric',
        'status' => 'required',
        'remark' => 'nullable|string',
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
    public function item()
    {
        return $this->belongsTo(\App\Models\Item::class, 'item_id');
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function vendor()
    {
        return $this->belongsTo(\App\Models\Vendor::class, 'vendor_id');
    }

    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    public function getArchivedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

}
