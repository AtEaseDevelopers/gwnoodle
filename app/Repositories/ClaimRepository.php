<?php

namespace App\Repositories;

use App\Models\Claim;
use App\Repositories\BaseRepository;

/**
 * Class ClaimRepository
 * @package App\Repositories
 * @version August 3, 2022, 10:24 am UTC
*/

class ClaimRepository extends BaseRepository
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
        return Claim::class;
    }
}
