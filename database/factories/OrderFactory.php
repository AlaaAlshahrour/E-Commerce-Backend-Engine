<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
/**
 * @extends Factory<Order>
 */

use Carbon\Carbon;
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
        $date = Carbon::now()->subDays(rand(0, 1));

        return [
            'user_id' => User::factory(),
            'status' => 'Completed',
            'payment_status' => 'paid',

            'total_amount' => fake()->randomFloat(2, 50, 1000),

            'created_at' => $date->copy()->setTime(
                rand(0, 23),
                rand(0, 59),
                rand(0, 59)
            ),
            'shipping_address' => $this->faker->address(),
            'updated_at' => now(),
        ];
    }
}
