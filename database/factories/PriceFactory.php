<?php

namespace Database\Factories;

use App\Models\Price;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Price::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'item_id' => $this->faker->word,
        'vendor_id' => $this->faker->word,
        'source_id' => $this->faker->word,
        'destinate_id' => $this->faker->word,
        'minrange' => $this->faker->randomDigitNotNull,
        'maxrange' => $this->faker->randomDigitNotNull,
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
