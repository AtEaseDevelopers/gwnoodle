<?php

namespace Database\Factories;

use App\Models\servicedetails;
use Illuminate\Database\Eloquent\Factories\Factory;

class servicedetailsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = servicedetails::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'lorry_id' => $this->faker->word,
        'type' => $this->faker->word,
        'date' => $this->faker->date('Y-m-d H:i:s'),
        'nextdate' => $this->faker->date('Y-m-d H:i:s'),
        'amount' => $this->faker->randomDigitNotNull,
        'remark' => $this->faker->word,
        'created_at' => $this->faker->date('Y-m-d H:i:s'),
        'updated_at' => $this->faker->date('Y-m-d H:i:s'),
        'deleted_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
