<?php

namespace App\Repositories;

use App\Models\InvoicePayment;
use App\Repositories\BaseRepository;

/**
 * Class InvoicePaymentRepository
 * @package App\Repositories
 * @version June 24, 2023, 5:41 pm +08
*/

class InvoicePaymentRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'invoice_id',
        'type',
        'customer_id',
        'amount',
        'status',
        'attachment',
        'approve_by',
        'approve_at',
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
        return InvoicePayment::class;
    }
}
