<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Class saveviews
 * @package App\Models
 * @version December 20, 2022, 2:01 pm +08
 *
 * @property integer $user_id
 * @property string|\Carbon\Carbon $date
 * @property string $view
 * @property integer $recordrow
 * @property string $data
 */
class saveviews extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'saveviews';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'user_id',
        'date',
        'view',
        'recordrow',
        'data'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'date' => 'datetime:d-m-Y h:m:s A',
        'view' => 'string',
        'recordrow' => 'integer',
        'data' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'user_id' => 'required',
        'date' => 'required',
        'view' => 'nullable|string|max:255',
        'recordrow' => 'nullable|integer',
        'data' => 'nullable|string',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y h:i:s A');
    }

    
}
