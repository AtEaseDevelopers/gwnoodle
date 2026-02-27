<?php

namespace App\Repositories;

use App\Models\DriverLocation;
use App\Repositories\BaseRepository;

/**
 * Class DriverLocationRepository
 * @package App\Repositories
 * @version August 31, 2023, 8:59 pm +08
*/

class DriverLocationRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'date',
        'driver_id',
        'kelindan_id',
        'lorry_id',
        'latitude',
        'longitude'
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
        return DriverLocation::class;
    }
}
