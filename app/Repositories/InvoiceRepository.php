<?php

namespace App\Repositories;

use App\Models\Invoice;
use App\Repositories\BaseRepository;

/**
 * Class InvoiceRepository
 * @package App\Repositories
 * @version June 24, 2023, 2:46 pm +08
*/

class InvoiceRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'invoiceno',
        'date',
        'customer_id',
        'driver_id',
        'kelindan_id',
        'agent_id',
        'supervisor_id',
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
        return Invoice::class;
    }
}
