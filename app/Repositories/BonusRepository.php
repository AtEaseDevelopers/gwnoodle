<?php

namespace App\Repositories;

use App\Models\Bonus;
use App\Repositories\BaseRepository;

/**
 * Class BonusRepository
 * @package App\Repositories
 * @version August 3, 2022, 8:42 am UTC
*/

class BonusRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'vendor_id',
        'source_id',
        'destinate_id',
        'weight',
        'bonusstart',
        'bonusend',
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
        return Bonus::class;
    }
}
