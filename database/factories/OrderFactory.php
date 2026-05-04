<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
/**
 * @extends Factory<Order>
 */
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
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
            'status' => fake()->randomElement(['Processing', 'Canceled', 'Completed', 'pending']),
            'total_amount' => fake()->randomFloat(2, 50, 2000),
            'shipping_address' => fake()->address(),
            'payment_method' => fake()->randomElement(['wallet', 'card', 'cash']),
            'payment_status' => fake()->randomElement(['pending', 'paid', 'failed', 'refunded']),
        ];
    }
}
