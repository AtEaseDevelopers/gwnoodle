<?php

namespace App\Repositories;

use App\Models\RoleHasPermission;
use App\Repositories\BaseRepository;

/**
 * Class RoleHasPermissionRepository
 * @package App\Repositories
 * @version July 16, 2022, 3:43 pm UTC
*/

class RoleHasPermissionRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'permission_id',
        'role_id'
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return RoleHasPermission::class;
    }

    public function where($field, $value)
    {
        return RoleHasPermission::where($field, $value);
    }
}
