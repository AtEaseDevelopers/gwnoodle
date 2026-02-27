<?php

namespace App\Repositories;

use App\Models\Loan;
use App\Repositories\BaseRepository;

/**
 * Class LoanRepository
 * @package App\Repositories
 * @version August 5, 2022, 2:47 pm UTC
*/

class LoanRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'date',
        'driver_id',
        'description',
        'amount',
        'period',
        'rate',
        'totalamount',
        'monthlyamount',
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
        return Loan::class;
    }
}
