<?php

namespace App\Repositories;

use App\Models\servicedetails;
use App\Repositories\BaseRepository;

/**
 * Class servicedetailsRepository
 * @package App\Repositories
 * @version February 4, 2023, 2:12 am +08
*/

class servicedetailsRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'lorry_id',
        'type',
        'date',
        'nextdate',
        'amount',
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
        return servicedetails::class;
    }
}
