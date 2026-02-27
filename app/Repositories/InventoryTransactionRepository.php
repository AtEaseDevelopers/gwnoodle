<?php

namespace App\Repositories;

use App\Models\InventoryTransaction;
use App\Repositories\BaseRepository;

/**
 * Class InventoryTransactionRepository
 * @package App\Repositories
 * @version July 1, 2023, 12:39 pm +08
*/

class InventoryTransactionRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'lorry_id',
        'product_id',
        'quantity',
        'type',
        'remark',
        'user'
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
        return InventoryTransaction::class;
    }
}
