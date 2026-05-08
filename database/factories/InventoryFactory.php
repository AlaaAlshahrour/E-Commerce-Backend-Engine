<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Product;
/**
 * @extends Factory<Inventory>
 */
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(0, 100),
        ];
    }
}
