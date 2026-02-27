<?php

namespace Database\Factories;

use App\Models\foc;
use Illuminate\Database\Eloquent\Factories\Factory;

class focFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = foc::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'product_id' => $this->faker->word,
        'customer_id' => $this->faker->word,
        'quantity' => $this->faker->randomDigitNotNull,
        'free_product_id' => $this->faker->word,
        'free_quantity' => $this->faker->randomDigitNotNull,
        'startdate' => $this->faker->date('Y-m-d H:i:s'),
        'enddate' => $this->faker->date('Y-m-d H:i:s'),
        'status' => $this->faker->word,
        'created_at' => $this->faker->date('Y-m-d H:i:s'),
        'updated_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
