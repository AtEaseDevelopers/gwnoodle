<?php

namespace App\Repositories;

use App\Models\paymentdetail;
use App\Repositories\BaseRepository;

/**
 * Class paymentdetailRepository
 * @package App\Repositories
 * @version September 23, 2022, 3:36 pm UTC
*/

class paymentdetailRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'driver_id',
        'datefrom',
        'dateto',
        'month',
        'do_amount',
        'do_list',
        'claim_amount',
        'claim_list',
        'comp_amount',
        'comp_list',
        'adv_amount',
        'adv_list',
        'loanpay_amount',
        'loanpay_list',
        'bonus_amount',
        'bonus_list',
        'deduct_amount',
        'final_amount',
        'status'
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
        return paymentdetail::class;
    }
}
