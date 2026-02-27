<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use App\Models\Code;

class Customer extends Model
{
    // use SoftDeletes;

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
        'chinese_name',
        'paymentterm',
        'group',
        'agent_id',
        'supervisor_id',
        'phone',
        'address',
        'status',
        'sst',
        //e-invoice fields
        'postcode',
        'city',
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
        'paymentterm' => 'integer',
        'group' => 'string',
        'agent_id' => 'integer',
        'supervisor_id' => 'integer',
        'phone' => 'string',
        'address' => 'string',
        'status' => 'integer',
        'sst' => 'string',
        'tin' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'code' => 'required|string|max:255|unique:customers,code',
        'company' => 'required|string|max:255|string|max:255',
        'paymentterm' => 'required',
        'phone' => 'nullable|string|max:20|nullable|string|max:20',
        'address' => 'nullable|string|max:65535|nullable|string|max:65535',
        'status' => 'required',
        'sst' => 'nullable|string|max:255',
        'tin' => 'nullable|string|max:255',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function agent()
    {
        return $this->belongsTo(\App\Models\Agent::class, 'agent_id', 'id');
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
