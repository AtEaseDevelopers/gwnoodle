<?php

namespace Database\Factories;

use App\Models\Loanpayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanpaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Loanpayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'loan_id' => $this->faker->word,
        'date' => $this->faker->date('Y-m-d H:i:s'),
        'description' => $this->faker->text,
        'amount' => $this->faker->randomDigitNotNull,
        'source' => $this->faker->word,
        'payment' => $this->faker->randomDigitNotNull,
        'status' => $this->faker->word,
        'STR_UDF1' => $this->faker->text,
        'STR_UDF2' => $this->faker->text,
        'STR_UDF3' => $this->faker->text,
        'INT_UDF1' => $this->faker->randomDigitNotNull,
        'INT_UDF2' => $this->faker->randomDigitNotNull,
        'INT_UDF3' => $this->faker->randomDigitNotNull,
        'created_at' => $this->faker->date('Y-m-d H:i:s'),
        'updated_at' => $this->faker->date('Y-m-d H:i:s'),
        'deleted_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
