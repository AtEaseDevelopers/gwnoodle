<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use App\Models\Code;
use App\Traits\LogsActivity;

class Customer extends Model
{
    // use SoftDeletes;
    use LogsActivity;
    use HasFactory;

    public $table = 'customers';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    public $appends = [
        'GroupDescription',
    ];

    public $fillable = [
        'code',
        'company',
        'customer_type',
        'paymentterm',
        'group',
        'driver_id',
        'phone',
        'billing_address',
        'delivery_address',
        'status',
        'category',
        'agent_id',
        'supervisor_id',

        //e-invoice fields
        'postcode',
        'area',
        'email',
        'state',
        'country',
        'registration_no',
        'msic',
        'sst_registration_no',
        'tourism_tax_registration',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'code' => 'string',
        'company' => 'string',
        'paymentterm' => 'string',
        'group' => 'array',
        'driver_id' => 'integer',
        'phone' => 'string',
        'billing_address' => 'string',
        'delivery_address' => 'string',
        'status' => 'integer',
        'category' => 'string',
        'agent_id' => 'integer',
        'supervisor_id' => 'integer',
        
        //e-invoice fields casts
        'postcode' => 'integer',
        'area' => 'string',
        'email' => 'string',
        'state' => 'string',
        'country' => 'string',
        'registration_no' => 'string',
        'msic' => 'string',
        'sst_registration_no' => 'string',
        'tourism_tax_registration' => 'string',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'code' => 'required|string|max:255|unique:customers,code',
        'company' => 'required|string|max:255',
        'paymentterm' => 'required',
        'group' => 'nullable|array',
        'driver_id' => 'required|integer|exists:drivers,id',
        'phone' => 'nullable|string|max:20',
        'billing_address' => 'nullable|string',
        'delivery_address' => 'nullable|string',
        'status' => 'required|boolean',
        'category' => 'nullable|string|max:100',
        'agent_id' => 'nullable|integer|exists:agents,id',
        'supervisor_id' => 'nullable|integer|exists:supervisors,id',
        
        //e-invoice validation rules
        'postcode' => 'nullable|integer|max:20',
        'area' => 'nullable|string|max:255',
        'email' => 'nullable|email|max:255',
        'state' => 'nullable|string|max:100',
        'country' => 'nullable|string|max:100',
        'registration_no' => 'nullable|string|max:255',
        'msic' => 'nullable|string|max:50',
        'sst_registration_no' => 'nullable|string|max:255',
        'tourism_tax_registration' => 'nullable|string|max:255',
    ];

    public static $paymentTerm = [
            'cash' => 'Cash',
            'credit_note' => 'Credit Note',
            'cod'=>'COD',
        ];
        
    public static $categoryOptions = [
            'business' => 'Business',
            'individual' => 'Individual',
            'government' => 'Government',
        ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function agent()
    {
        return $this->belongsTo(\App\Models\Agent::class, 'agent_id', 'id');
    }

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function groups()
    {
        return $this->belongsTo(\App\Models\Code::class, 'group', 'value')->where('code','customer_group');
    }

    public function supervisor()
    {
        return $this->belongsTo(\App\Models\Supervisor::class, 'supervisor_id', 'id');
    }

    public function foc(){
        return $this->hasMany(\App\Models\foc::class, 'customer_id', 'id');
    }

    public function activefoc(){
        return $this->foc()->where('startdate','<=',date('Y-m-d H:i:s'))->where('enddate','>',date('Y-m-d H:i:s'))->where('status',1);
    }

    public function specialprice(){
        return $this->hasMany(\App\Models\SpecialPrice::class, 'customer_id', 'id');
    }

    public function normalprice(){
        return $this->specialprice()->hasMany(\App\Models\Product::class);
    }

    public function getGroupDescriptionAttribute(){
        return Code::where('code','customer_group')->whereRaw('find_in_set(codes.value, "'.$this->group.'")')->select(DB::raw("GROUP_CONCAT(codes.description) as group_descr"))->get()->first()->group_descr ?? '';
    }

}