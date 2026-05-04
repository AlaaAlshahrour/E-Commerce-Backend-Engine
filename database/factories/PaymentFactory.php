<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
/**
 * @extends Factory<Payment>
 */
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'type' => fake()->randomElement(['payment', 'refund']),
            'amount' => fake()->randomFloat(2, 10, 2000),
            'status' => fake()->randomElement(['pending', 'completed', 'failed']),
            'transaction_id' => Transaction::factory(),
        ];
    }
}
