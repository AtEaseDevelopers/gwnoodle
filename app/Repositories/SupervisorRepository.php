<?php

namespace App\Repositories;

use App\Models\Supervisor;
use App\Repositories\BaseRepository;

/**
 * Class SupervisorRepository
 * @package App\Repositories
 * @version June 20, 2023, 6:23 pm +08
*/

class SupervisorRepository extends BaseRepository
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
        'remark'
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
        return Supervisor::class;
    }
}
