<?php

namespace App\Repositories;

use App\Models\Location;
use App\Repositories\BaseRepository;

/**
 * Class LocationRepository
 * @package App\Repositories
 * @version July 23, 2022, 4:23 pm UTC
*/

class LocationRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'code',
        'name',
        'source',
        'distination',
        'phone',
        'address1',
        'address2',
        'address3',
        'address4',
        'status',
        'STR_UDF1',
        'STR_UDF2',
        'STR_UDF3',
        'INT_UDF1',
        'INT_UDF2',
        'INT_UDF3'
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
        return Location::class;
    }
}
