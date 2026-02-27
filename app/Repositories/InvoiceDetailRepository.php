<?php

namespace App\Repositories;

use App\Models\InvoiceDetail;
use App\Repositories\BaseRepository;

/**
 * Class InvoiceDetailRepository
 * @package App\Repositories
 * @version June 24, 2023, 3:24 pm +08
*/

class InvoiceDetailRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'invoice_id',
        'product_id',
        'quantity',
        'price',
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
        return InvoiceDetail::class;
    }
}
