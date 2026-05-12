<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CartItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [];

        $carts = Cart::pluck('id');
        $products = Product::pluck('id');

        foreach ($carts as $cartId) {

            $randomProducts = $products->random(rand(1, 5));

            foreach ($randomProducts as $productId) {

                $rows[] = [
                    'cart_id' => $cartId,
                    'product_id' => $productId,
                    'quantity' => rand(1, 5),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('cart_items')->insert($rows);
    }
}
