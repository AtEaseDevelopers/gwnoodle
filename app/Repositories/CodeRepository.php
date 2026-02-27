<?php

namespace App\Repositories;

use App\Models\Code;
use App\Repositories\BaseRepository;

/**
 * Class CodeRepository
 * @package App\Repositories
 * @version July 16, 2022, 1:08 pm UTC
*/

class CodeRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'code',
        'description',
        'value',
        'sequence',
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
        return Code::class;
    }
}
