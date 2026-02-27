<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'invoiceno' => $this->faker->word,
        'date' => $this->faker->date('Y-m-d H:i:s'),
        'customer_id' => $this->faker->randomDigitNotNull,
        'driver_id' => $this->faker->randomDigitNotNull,
        'kelindan_id' => $this->faker->randomDigitNotNull,
        'agent_id' => $this->faker->randomDigitNotNull,
        'supervisor_id' => $this->faker->randomDigitNotNull,
        'status' => $this->faker->word,
        'remark' => $this->faker->word,
        'created_at' => $this->faker->date('Y-m-d H:i:s'),
        'updated_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
