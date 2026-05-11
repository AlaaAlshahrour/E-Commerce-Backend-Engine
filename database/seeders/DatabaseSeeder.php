<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CartRaceSeeder::class,

            UserSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            InventorySeeder::class,
            WalletSeeder::class,
            CartSeeder::class,
            CartItemSeeder::class,
            OrderSeeder::class,
            OrderItemSeeder::class,
            TransactionSeeder::class,
        ]);
    }
}
