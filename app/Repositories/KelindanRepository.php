<?php

namespace App\Repositories;

use App\Models\Kelindan;
use App\Repositories\BaseRepository;

/**
 * Class KelindanRepository
 * @package App\Repositories
 * @version June 20, 2023, 5:02 pm +08
*/

class KelindanRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'employeeid',
        'name',
        'ic',
        'phone',
        'bankdetails1',
        'bankdetails2',
        'firstvaccine',
        'secondvaccine',
        'temperature',
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
        return Kelindan::class;
    }
}
