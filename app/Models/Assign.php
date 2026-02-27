<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Class Assign
 * @package App\Models
 * @version June 21, 2023, 6:30 pm +08
 *
 * @property integer $driver_id
 * @property integer $customer_id
 */
class Assign extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'assigns';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';



    public $fillable = [
        'driver_id',
        'customer_id',
        'sequence'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'driver_id' => 'integer',
        'customer_id' => 'integer',
        'sequence' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'driver_id' => 'required',
        'customer_id' => 'required',
        'sequence' => 'required',
        'created_at' => 'nullable|nullable',
        'updated_at' => 'nullable|nullable'
    ];

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id');
    }

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id');
    }
    
}
