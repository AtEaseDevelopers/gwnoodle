<?php

namespace Database\Factories;

use App\Models\InventoryTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryTransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = InventoryTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'lorry_id' => $this->faker->word,
        'product_id' => $this->faker->word,
        'quantity' => $this->faker->word,
        'type' => $this->faker->word,
        'remark' => $this->faker->word,
        'user' => $this->faker->word,
        'created_at' => $this->faker->date('Y-m-d H:i:s'),
        'updated_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
