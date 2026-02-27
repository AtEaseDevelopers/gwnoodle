<?php

namespace Database\Factories;

use App\Models\Supervisor;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupervisorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Supervisor::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'employeeid' => $this->faker->word,
        'name' => $this->faker->word,
        'ic' => $this->faker->word,
        'phone' => $this->faker->word,
        'bankdetails1' => $this->faker->word,
        'bankdetails2' => $this->faker->word,
        'firstvaccine' => $this->faker->date('Y-m-d H:i:s'),
        'secondvaccine' => $this->faker->date('Y-m-d H:i:s'),
        'temperature' => $this->faker->randomDigitNotNull,
        'status' => $this->faker->word,
        'remark' => $this->faker->word,
        'created_at' => $this->faker->date('Y-m-d H:i:s'),
        'updated_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
