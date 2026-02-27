<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class DriverLocation extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'driver_location';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'date',
        'driver_id',
        'kelindan_id',
        'lorry_id',
        'latitude',
        'longitude',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date:d-m-Y',
        'driver_id' => 'integer',
        'kelindan_id' => 'integer',
        'lorry_id' => 'integer',
        'latitude' => 'string',
        'longitude' => 'string',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'date' => 'required|date',
        'driver_id' => 'required|numeric',
        'kelindan_id' => 'required|numeric',
        'lorry_id' => 'required|numeric',
        'latitude' => 'required|string|max:255',
        'longitude' => 'required|string|max:255',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id', 'id');
    }

    public function kelindan()
    {
        return $this->belongsTo(\App\Models\Kelindan::class, 'kelindan_id', 'id');
    }

    public function lorry()
    {
        return $this->belongsTo(\App\Models\Lorry::class, 'lorry_id', 'id');
    }

    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y H:i:s');
    }

}
