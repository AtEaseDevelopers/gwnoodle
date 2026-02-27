<?php

namespace Database\Factories;

use App\Models\SpecialPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpecialPriceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SpecialPrice::class;

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
        'price' => $this->faker->randomDigitNotNull,
        'status' => $this->faker->word,
        'created_at' => $this->faker->date('Y-m-d H:i:s'),
        'updated_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
