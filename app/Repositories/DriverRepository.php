<?php

namespace App\Repositories;

use App\Models\Driver;
use App\Repositories\BaseRepository;

/**
 * Class DriverRepository
 * @package App\Repositories
 * @version July 23, 2022, 10:01 am UTC
*/

class DriverRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'employeeid',
        'name',
        'ic',
        'phone',
        'bankdetails1',
        'bankdetails2',
        'firstvaccine',
        'secondvaccine',
        'temperature',
        'status',
        'remark',
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
        return Driver::class;
    }
}
