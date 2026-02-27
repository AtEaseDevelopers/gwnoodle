<?php

namespace Database\Factories;

use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Driver::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
        'birthdate' => $this->faker->date('Y-m-d H:i:s'),
        'email' => $this->faker->word,
        'phone' => $this->faker->word,
        'address1' => $this->faker->text,
        'address2' => $this->faker->text,
        'address3' => $this->faker->text,
        'address4' => $this->faker->text,
        'grouping' => $this->faker->word,
        'caption' => $this->faker->word,
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
