<?php

namespace App\Repositories;

use App\Models\ProductBatch;
use App\Repositories\BaseRepository;

/**
 * Class ProductBatchRepository
 * @package App\Repositories
 * @version June 20, 2023
 */

class ProductBatchRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'product_id',
        'batch_code',
        'manufacturing_date',
        'expiry_date',
        'quantity',
        'initial_quantity',
        'qr_code',
        'barcode_data',
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
        return ProductBatch::class;
    }

    /**
     * Find batches by product
     */
    public function findByProduct($productId)
    {
        return $this->model->where('product_id', $productId)->get();
    }

    /**
     * Find active batches
     */
    public function findActive()
    {
        return $this->model->where('status', 1)
            ->where('quantity', '>', 0)
            ->where('expiry_date', '>', now())
            ->get();
    }

    /**
     * Find expiring batches
     */
    public function findExpiring($days = 30)
    {
        return $this->model->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now())
            ->where('quantity', '>', 0)
            ->get();
    }
}