<?php

namespace Database\Factories;

use App\Models\saveviews;
use Illuminate\Database\Eloquent\Factories\Factory;

class saveviewsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = saveviews::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => $this->faker->word,
        'date' => $this->faker->date('Y-m-d H:i:s'),
        'view' => $this->faker->word,
        'recordrow' => $this->faker->randomDigitNotNull,
        'data' => $this->faker->text,
        'created_at' => $this->faker->date('Y-m-d H:i:s'),
        'updated_at' => $this->faker->date('Y-m-d H:i:s'),
        'deleted_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
