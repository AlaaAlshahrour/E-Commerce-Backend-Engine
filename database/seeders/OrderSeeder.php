<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [];

        for ($i = 0; $i < 50000; $i++) {

            $rows[] = [
                'user_id' => rand(1, 1000),
                'status' => 'Completed',
                'total_amount' => rand(50, 500),
                'shipping_address' => 'Damascus',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($rows) >= 5000) {
                DB::table('orders')->insert($rows);
                $rows = [];
            }
        }

        DB::table('orders')->insert($rows);
    }
}
