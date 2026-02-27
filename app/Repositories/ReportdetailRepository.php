<?php

namespace App\Repositories;

use App\Models\Reportdetail;
use App\Repositories\BaseRepository;

/**
 * Class ReportdetailRepository
 * @package App\Repositories
 * @version August 14, 2022, 2:19 pm UTC
*/

class ReportdetailRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'report_id',
        'name',
        'title',
        'type',
        'data',
        'sequence',
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
        return Reportdetail::class;
    }
}
