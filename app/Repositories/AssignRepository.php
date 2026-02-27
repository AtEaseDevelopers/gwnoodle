<?php

namespace App\Repositories;

use App\Models\Assign;
use App\Repositories\BaseRepository;

/**
 * Class AssignRepository
 * @package App\Repositories
 * @version June 21, 2023, 6:30 pm +08
*/

class AssignRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'driver_id',
        'customer_id',
        'sequence'
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
        return Assign::class;
    }
}
