<?php

namespace App\Repositories;

use App\Models\Loanpayment;
use App\Repositories\BaseRepository;

/**
 * Class LoanpaymentRepository
 * @package App\Repositories
 * @version August 5, 2022, 2:48 pm UTC
*/

class LoanpaymentRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'loan_id',
        'date',
        'description',
        'amount',
        'source',
        'payment',
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
        return Loanpayment::class;
    }
}
