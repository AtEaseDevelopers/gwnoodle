<?php

namespace App\Repositories;

use App\Models\ArcDeliveryOrder;
use App\Repositories\BaseRepository;

/**
 * Class DeliveryOrderRepository
 * @package App\Repositories
 * @version August 13, 2022, 2:11 pm UTC
*/

class ArcDeliveryOrderRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'dono',
        'date',
        'driver_id',
        'lorry_id',
        'vendor_id',
        'source_id',
        'destinate_id',
        'item_id',
        'weight',
        'fees',
        'billingrate',
        'commissionrate',
        'status',
        'STR_UDF1',
        'STR_UDF2',
        'STR_UDF3',
        'INT_UDF1',
        'INT_UDF2',
        'INT_UDF3',
        'archived_at'
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
        return ArcDeliveryOrder::class;
    }
}
