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

        function getRandomStatus()
        {
            $rand = rand(1, 100);

            if ($rand <= 50) {
                return 'Completed';
            } elseif ($rand <= 75) {
                return 'Processing';
            } elseif ($rand <= 90) {
                return 'Pending';
            } else {
                return 'Canceled';
            }
        }

        for ($i = 0; $i < 50000; $i++) {

            $rows[] = [
                'user_id' => rand(1, 1000),
                'status' => getRandomStatus(),
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
