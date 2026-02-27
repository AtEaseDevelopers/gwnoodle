<?php

namespace App\Repositories;

use App\Models\SpecialPrice;
use App\Repositories\BaseRepository;

/**
 * Class SpecialPriceRepository
 * @package App\Repositories
 * @version June 20, 2023, 10:18 pm +08
*/

class SpecialPriceRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'product_id',
        'customer_id',
        'price',
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
        return SpecialPrice::class;
    }
}
