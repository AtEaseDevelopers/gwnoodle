<?php

namespace App\Repositories;

use App\Models\Company;
use App\Repositories\BaseRepository;

/**
 * Class CompanyRepository
 * @package App\Repositories
 * @version December 13, 2023, 7:26 pm +08
*/

class CompanyRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'code',
        'name',
        'ssm',
        'address1',
        'address2',
        'address3',
        'address4',
        'group_id'
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
        return Company::class;
    }
}
