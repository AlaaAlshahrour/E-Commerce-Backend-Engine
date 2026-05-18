<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
/**
 * @extends Factory<OrderItem>
 */
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::inRandomOrder()->first()->id ?? Product::factory(),
            'order_id' => Order::inRandomOrder()->first()->id ?? Order::factory(),
            'quantity' => fake()->numberBetween(1, 5),
            'unit_price' => fake()->randomFloat(2, 10, 500),
        ];
    }
}
