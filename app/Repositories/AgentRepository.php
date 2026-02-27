<?php

namespace App\Repositories;

use App\Models\Agent;
use App\Repositories\BaseRepository;

/**
 * Class AgentRepository
 * @package App\Repositories
 * @version June 20, 2023, 5:56 pm +08
*/

class AgentRepository extends BaseRepository
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
        return Agent::class;
    }
}
