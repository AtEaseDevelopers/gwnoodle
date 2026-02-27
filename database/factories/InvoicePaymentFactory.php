<?php

namespace Database\Factories;

use App\Models\InvoicePayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoicePaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = InvoicePayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'invoice_id' => $this->faker->randomDigitNotNull,
        'type' => $this->faker->word,
        'customer_id' => $this->faker->randomDigitNotNull,
        'amount' => $this->faker->randomDigitNotNull,
        'status' => $this->faker->word,
        'attachment' => $this->faker->word,
        'approve_by' => $this->faker->word,
        'approve_at' => $this->faker->date('Y-m-d H:i:s'),
        'remark' => $this->faker->word,
        'created_at' => $this->faker->date('Y-m-d H:i:s'),
        'updated_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
