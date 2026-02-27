<?php

namespace App\Repositories;

use App\Models\foc;
use App\Repositories\BaseRepository;

/**
 * Class focRepository
 * @package App\Repositories
 * @version June 20, 2023, 10:49 pm +08
*/

class focRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'product_id',
        'customer_id',
        'quantity',
        'free_product_id',
        'free_quantity',
        'startdate',
        'enddate',
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
        return foc::class;
    }
}
