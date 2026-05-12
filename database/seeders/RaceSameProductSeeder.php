<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RaceSameProductSeeder extends Seeder
{
    public function run(): void
    {
        // ── Category ────────────────────────────────────────────────────────
        DB::table('categories')->insertOrIgnore([
            ['id' => 1, 'name' => 'Electronics', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Shared product — qty = 1, the scarcity is intentional ──────────
        DB::table('products')->insertOrIgnore([[
            'id'          => 101,
            'name'        => 'Limited Edition Sneaker (Last Pair)',
            'description' => 'Only 1 unit in stock. Used to demonstrate overselling.',
            'price'       => 199.99,
            'photo_url'   => null,
            'category_id' => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]]);

        DB::table('inventories')->insertOrIgnore([[
            'product_id' => 101,
            'quantity'   => 1,   // ← intentionally 1 — only one buyer should succeed
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        // ── Users ───────────────────────────────────────────────────────────
        DB::table('users')->insertOrIgnore([
            [
                'id'                => 5,
                'name'              => 'Buyer One',
                'email'             => 'buyer1@example.com',
                'email_verified_at' => now(),
                'role'              => 'User',
                'password'          => Hash::make('password'),
                'remember_token'    => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'id'                => 6,
                'name'              => 'Buyer Two',
                'email'             => 'buyer2@example.com',
                'email_verified_at' => now(),
                'role'              => 'User',
                'password'          => Hash::make('password'),
                'remember_token'    => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ]);

        // ── Wallets — $500 each, well above the $199.99 product price ───────
        DB::table('wallets')->insertOrIgnore([
            ['id' => 5, 'user_id' => 5, 'balance' => 500.00, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'user_id' => 6, 'balance' => 500.00, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('transactions')->insert([
            ['wallet_id' => 5, 'order_id' => null, 'amount' => 500.00, 'balance_before' => 0.00, 'balance_after' => 500.00, 'type' => 'deposit', 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()],
            ['wallet_id' => 6, 'order_id' => null, 'amount' => 500.00, 'balance_before' => 0.00, 'balance_after' => 500.00, 'type' => 'deposit', 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Carts — both containing the same product 101 ────────────────────
        DB::table('carts')->insertOrIgnore([
            ['id' => 5, 'user_id' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'user_id' => 6, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('cart_items')->insertOrIgnore([
            ['cart_id' => 5, 'product_id' => 101, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['cart_id' => 6, 'product_id' => 101, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

  }
}
