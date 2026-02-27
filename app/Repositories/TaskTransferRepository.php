<?php

namespace App\Repositories;

use App\Models\TaskTransfer;
use App\Repositories\BaseRepository;

/**
 * Class TaskTransferRepository
 * @package App\Repositories
 * @version July 20, 2023, 6:54 pm +08
*/

class TaskTransferRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'from_driver_id',
        'to_driver_id',
        'task_id'
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
        return TaskTransfer::class;
    }
}
