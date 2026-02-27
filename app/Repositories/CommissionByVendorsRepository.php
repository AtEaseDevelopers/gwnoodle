<?php

namespace App\Repositories;

use App\Models\CommissionByVendors;
use App\Repositories\BaseRepository;

/**
 * Class CommissionByVendorsRepository
 * @package App\Repositories
 * @version August 30, 2022, 1:49 pm UTC
*/

class CommissionByVendorsRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'lorry_id',
        'vendor_id',
        'commissionlimit',
        'commissionpercentage',
        'status',
        'STR_UDF1',
        'STR_UDF2',
        'STR_UDF3',
        'INT_UDF1',
        'INT_UDF2',
        'INT_UDF3'
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
        return CommissionByVendors::class;
    }
}
