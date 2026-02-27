<?php

namespace App\Repositories;

use App\Models\InventoryTransfer;
use App\Repositories\BaseRepository;

/**
 * Class InventoryTransferRepository
 * @package App\Repositories
 * @version July 4, 2023, 9:51 pm +08
*/

class InventoryTransferRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'from_driver_id',
        'from_lorry_id',
        'to_driver_id',
        'to_lorry_id',
        'product_id',
        'quantity',
        'status',
        'remark'
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
        return InventoryTransfer::class;
    }
}
