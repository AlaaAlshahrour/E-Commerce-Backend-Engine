<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Wallet;
/**
 * @extends Factory<Transaction>
 */
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 10, 1000);
        $balanceBefore = fake()->randomFloat(2, 1000, 5000);

        return [
            'wallet_id' => Wallet::factory(),
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore + $amount,
            'type' => fake()->randomElement(['deposit', 'withdraw', 'payment', 'refund']),
            'status' => fake()->randomElement(['pending', 'completed', 'failed']),
            'reference_type' => fake()->randomElement(['order', 'topup', 'refund']),
            'reference_id' => fake()->numberBetween(1, 1000),
        ];
    }
}
