<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Item::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code' => $this->faker->word,
        'name' => $this->faker->word,
        'billingrate' => $this->faker->randomDigitNotNull,
        'commissionrate' => $this->faker->randomDigitNotNull,
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
