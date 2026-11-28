<?php

namespace Database\Factories;

use App\Models\StaffActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffActivity>
 */
class StaffActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'actor_id' => User::factory(),
            'field' => 'first_name',
            'from_value' => fake()->firstName(),
            'to_value' => fake()->firstName(),
        ];
    }
}
