<?php

namespace Database\Factories;

use App\Models\DriverLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverLocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DriverLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'date' => $this->faker->date('Y-m-d H:i:s'),
        'driver_id' => $this->faker->word,
        'kelindan_id' => $this->faker->word,
        'lorry_id' => $this->faker->word,
        'latitude' => $this->faker->word,
        'longitude' => $this->faker->word,
        'created_at' => $this->faker->date('Y-m-d H:i:s'),
        'updated_at' => $this->faker->date('Y-m-d H:i:s'),
        'deleted_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
