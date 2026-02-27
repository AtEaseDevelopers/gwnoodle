<?php

namespace Database\Factories;

use App\Models\TaskTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskTransferFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TaskTransfer::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'date' => $this->faker->date('Y-m-d H:i:s'),
        'from_driver_id' => $this->faker->word,
        'to_driver_id' => $this->faker->word,
        'task_id' => $this->faker->word,
        'created_at' => $this->faker->date('Y-m-d H:i:s'),
        'updated_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
