<?php

namespace Database\Factories;

use App\Models\RoleHasPermission;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleHasPermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RoleHasPermission::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'permission_id' => $this->faker->word,
        'role_id' => $this->faker->word
        ];
    }
}
