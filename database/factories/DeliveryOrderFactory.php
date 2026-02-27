<?php

namespace Database\Factories;

use App\Models\DeliveryOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryOrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DeliveryOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'dono' => $this->faker->word,
        'date' => $this->faker->date('Y-m-d H:i:s'),
        'driver_id' => $this->faker->word,
        'lorry_id' => $this->faker->word,
        'vendor_id' => $this->faker->word,
        'source_id' => $this->faker->word,
        'destinate_id' => $this->faker->word,
        'item_id' => $this->faker->word,
        'weight' => $this->faker->randomDigitNotNull,
        'fees' => $this->faker->randomDigitNotNull,
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
