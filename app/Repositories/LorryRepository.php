<?php

namespace App\Repositories;

use App\Models\Lorry;
use App\Repositories\BaseRepository;

/**
 * Class LorryRepository
 * @package App\Repositories
 * @version July 23, 2022, 11:31 am UTC
*/

class LorryRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'lorryno',
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
        return Lorry::class;
    }
}
