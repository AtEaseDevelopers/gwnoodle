<?php

namespace App\Repositories;

use App\Models\saveviews;
use App\Repositories\BaseRepository;

/**
 * Class saveviewsRepository
 * @package App\Repositories
 * @version December 20, 2022, 2:01 pm +08
*/

class saveviewsRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'user_id',
        'date',
        'view',
        'recordrow',
        'data'
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
        return saveviews::class;
    }
}
