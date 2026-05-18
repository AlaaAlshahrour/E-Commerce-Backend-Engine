<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartItem;
/**
 * @extends Factory<CartItem>
 */
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartItemFactory extends Factory
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
            'cart_id' => Cart::inRandomOrder()->first()->id ?? Cart::factory(),
            'quantity' => fake()->numberBetween(1, 5),
        ];
    }
}
