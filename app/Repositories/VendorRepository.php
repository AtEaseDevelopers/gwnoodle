<?php

namespace App\Repositories;

use App\Models\Vendor;
use App\Repositories\BaseRepository;

/**
 * Class VendorRepository
 * @package App\Repositories
 * @version July 23, 2022, 3:58 pm UTC
*/

class VendorRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'code',
        'name',
        'phone',
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
        return Vendor::class;
    }
}
