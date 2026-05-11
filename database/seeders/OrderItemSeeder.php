<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderItemSeeder extends Seeder
{
    public function run(): void
    {
        $orders = Order::pluck('id');
        $products = Product::all();

        $rows = [];

        foreach ($orders as $orderId) {

            $itemsCount = rand(1, 3);

            $randomProducts = $products->random($itemsCount);

            foreach ($randomProducts as $product) {

                $quantity = rand(1, 5);

                $rows[] = [
                    'order_id' => $orderId,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // كل 5000 سجل اعمل insert
                if (count($rows) >= 5000) {
                    DB::table('order_items')->insert($rows);
                    $rows = [];
                }
            }
        }

        // المتبقي
        if (!empty($rows)) {
            DB::table('order_items')->insert($rows);
        }
    }
}
