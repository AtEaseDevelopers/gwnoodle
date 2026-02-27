<?php

namespace Database\Factories;

use App\Models\InventoryTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryTransferFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = InventoryTransfer::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'from_driver_id' => $this->faker->word,
        'from_lorry_id' => $this->faker->word,
        'to_driver_id' => $this->faker->word,
        'to_lorry_id' => $this->faker->word,
        'product_id' => $this->faker->word,
        'quantity' => $this->faker->word,
        'status' => $this->faker->word,
        'remark' => $this->faker->word,
        'created_at' => $this->faker->date('Y-m-d H:i:s'),
        'updated_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
