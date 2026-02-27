<?php

namespace Database\Factories;

use App\Models\paymentdetail;
use Illuminate\Database\Eloquent\Factories\Factory;

class paymentdetailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = paymentdetail::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'driver_id' => $this->faker->word,
        'datefrom' => $this->faker->date('Y-m-d H:i:s'),
        'dateto' => $this->faker->date('Y-m-d H:i:s'),
        'month' => $this->faker->word,
        'do_amount' => $this->faker->randomDigitNotNull,
        'do_list' => $this->faker->text,
        'claim_amount' => $this->faker->randomDigitNotNull,
        'claim_list' => $this->faker->text,
        'comp_amount' => $this->faker->randomDigitNotNull,
        'comp_list' => $this->faker->text,
        'adv_amount' => $this->faker->randomDigitNotNull,
        'adv_list' => $this->faker->text,
        'loanpay_amount' => $this->faker->randomDigitNotNull,
        'loanpay_list' => $this->faker->text,
        'bonus_amount' => $this->faker->randomDigitNotNull,
        'bonus_list' => $this->faker->text,
        'deduct_amount' => $this->faker->randomDigitNotNull,
        'final_amount' => $this->faker->randomDigitNotNull,
        'status' => $this->faker->word,
        'created_at' => $this->faker->date('Y-m-d H:i:s'),
        'updated_at' => $this->faker->date('Y-m-d H:i:s'),
        'deleted_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
