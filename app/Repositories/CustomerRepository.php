<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Repositories\BaseRepository;

/**
 * Class CustomerRepository
 * @package App\Repositories
 * @version June 20, 2023, 8:17 pm +08
*/

class CustomerRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'code',
        'name',
        'paymentterm',
        'agent_id',
        'supervisor_id',
        'phone',
        'address',
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
        return Customer::class;
    }
}
