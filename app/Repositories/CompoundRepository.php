<?php

namespace App\Repositories;

use App\Models\Compound;
use App\Repositories\BaseRepository;

/**
 * Class CompoundRepository
 * @package App\Repositories
 * @version August 4, 2022, 2:53 pm UTC
*/

class CompoundRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'date',
        'no',
        'driver_id',
        'lorry_id',
        'description',
        'amount',
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
        return Compound::class;
    }
}
