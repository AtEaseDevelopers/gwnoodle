<?php

namespace App\Repositories;

use App\Models\UserHasRole;
use App\Repositories\BaseRepository;

/**
 * Class UserHasRoleRepository
 * @package App\Repositories
 * @version July 16, 2022, 4:54 pm UTC
*/

class UserHasRoleRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'role_id',
        'model_type',
        'model_id'
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
        return UserHasRole::class;
    }

    public function where($field, $value)
    {
        return UserHasRole::where($field, $value);
    }
    
}
