<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class RoleHasPermission
 * @package App\Models
 * @version July 16, 2022, 3:33 pm UTC
 *
 * @property \App\Models\Permission $permission
 * @property \App\Models\Role $role
 * @property integer $permission_id
 * @property integer $role_id
 */
class RoleHasPermission extends Model
{
    use HasFactory;

    public $table = 'role_has_permissions';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    public $timestamps = false;

    protected $dates = ['deleted_at'];



    public $fillable = [
        'role_id',
        'permission_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'role_id' => 'integer',
        'permission_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'role_id' => 'required',
        'permission_id' => 'required'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function permission()
    {
        return $this->belongsTo(\App\Models\Permission::class, 'permission_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function role()
    {
        return $this->belongsTo(\App\Models\Role::class, 'role_id');
    }
}
