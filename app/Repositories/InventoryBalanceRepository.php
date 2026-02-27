<?php

namespace App\Repositories;

use App\Models\InventoryBalance;
use App\Repositories\BaseRepository;

/**
 * Class InventoryBalanceRepository
 * @package App\Repositories
 * @version July 1, 2023, 11:18 am +08
*/

class InventoryBalanceRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'lorry_id',
        'product_id',
        'quantity'
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
        return InventoryBalance::class;
    }
}
