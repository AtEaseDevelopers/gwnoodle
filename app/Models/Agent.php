<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class Agent extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'agents';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';



    public $fillable = [
        'employeeid',
        'name',
        'ic',
        'phone',
        'commissionrate',
        'bankdetails1',
        'bankdetails2',
        'firstvaccine',
        'secondvaccine',
        'temperature',
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
        'employeeid' => 'string',
        'id' => 'integer',
        'name' => 'string',
        'ic' => 'string',
        'phone' => 'string',
        'commissionrate' => 'float',
        'bankdetails1' => 'string',
        'bankdetails2' => 'string',
        'firstvaccine' => 'date:d-m-Y',
        'secondvaccine' => 'date:d-m-Y',
        'temperature' => 'float',
        'status' => 'integer',
        'remark' => 'string',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'employeeid' => 'required|string|max:20|unique:agents,employeeid',
        'name' => 'required|string|max:255',
        'ic' => 'nullable|string|max:20|unique:drivers,ic',
        'phone' => 'nullable|string|max:255',
        // 'commissionrate' => 'required|numeric|min:0|max:100',
        'bankdetails1' => 'nullable|string|max:255',
        'bankdetails2' => 'nullable|string|max:255',
        'firstvaccine' => 'nullable',
        'secondvaccine' => 'nullable',
        'temperature' => 'nullable|numeric',
        'status' => 'required',
        'remark' => 'nullable|string|max:255',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    public function getFirstVaccineAttribute($value)
    {
        if($value != ''){
            return Carbon::parse($value)->format('d-m-Y');
        }else{
            return '';
        }
    }

    public function getSecondVaccineAttribute($value)
    {
        if($value != ''){
            return Carbon::parse($value)->format('d-m-Y');
        }else{
            return '';
        }
    }


}
