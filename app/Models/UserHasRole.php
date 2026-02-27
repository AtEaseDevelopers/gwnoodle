<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class UserHasRole
 * @package App\Models
 * @version July 16, 2022, 4:54 pm UTC
 *
 * @property \App\Models\Role $role
 * @property integer $role_id
 * @property string $model_type
 * @property integer $model_id
 */
class UserHasRole extends Model
{
    public $table = 'model_has_roles';
    

    protected $dates = ['deleted_at'];
    public $timestamps = false;



    public $fillable = [
        'role_id',
        'model_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'role_id' => 'integer',
        'model_type' => 'string',
        'model_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'role_id' => 'required',
        'model_id' => 'required'
    ];

    protected $attributes = [
        'model_type' => 'App\Models\User'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function role()
    {
        return $this->belongsTo(\App\Models\Role::class, 'role_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'model_id');
    }
}
