<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
/**
 * @extends Factory<Wallet>
 */
use Illuminate\Database\Eloquent\Factories\Factory;

class WalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => $this->faker->randomElement(User::pluck('id')->toArray()) ?? User::factory(),
            'balance' => fake()->randomFloat(2, 0, 10000),
            'is_active' => true,
        ];
    }
}
