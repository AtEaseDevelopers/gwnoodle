<?php

namespace App\Repositories;

use App\Models\Trip;
use App\Repositories\BaseRepository;

/**
 * Class TripRepository
 * @package App\Repositories
 * @version June 25, 2023, 7:19 pm +08
*/

class TripRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'date',
        'driver_id',
        'kelindan_id',
        'lorry_id',
        'type'
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
        return Trip::class;
    }
}
