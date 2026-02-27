<?php

namespace App\Repositories;

use App\Models\Task;
use App\Repositories\BaseRepository;

/**
 * Class TaskRepository
 * @package App\Repositories
 * @version June 25, 2023, 5:12 pm +08
*/

class TaskRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'date',
        'driver_id',
        'customer_id',
        'sequence',
        'invoice_id',
        'status'
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
        return Task::class;
    }
}
