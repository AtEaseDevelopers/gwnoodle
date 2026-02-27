<?php

namespace App\Repositories;

use App\Models\Price;
use App\Repositories\BaseRepository;

/**
 * Class PriceRepository
 * @package App\Repositories
 * @version August 2, 2022, 2:08 pm UTC
*/

class PriceRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'item_id',
        'vendor_id',
        'source_id',
        'destinate_id',
        'minrange',
        'maxrange',
        'billingrate',
        'commissionrate',
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
        return Price::class;
    }
}
