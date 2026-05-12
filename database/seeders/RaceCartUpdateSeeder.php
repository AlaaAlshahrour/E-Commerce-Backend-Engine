<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RaceCartUpdateSeeder extends Seeder
{
    public function run(): void
    {
        // ── Products ────────────────────────────────────────────────────────
        DB::table('categories')->insertOrIgnore([
            ['id' => 1, 'name' => 'Electronics', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('products')->insertOrIgnore([
            ['id' => 1, 'name' => 'Wireless Headphones', 'description' => 'Over-ear BT headphones.', 'price' => 99.99, 'photo_url' => null, 'category_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Stock = 50, so any quantity 1–10 (used in the k6 script) is always valid
        DB::table('inventories')->insertOrIgnore([
            ['product_id' => 1, 'quantity' => 50, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── User ────────────────────────────────────────────────────────────
        DB::table('users')->insertOrIgnore([[
            'id'                => 4,
            'name'              => 'Double Checkout Tester',
            'email'             => 'double@example.com',
            'email_verified_at' => now(),
            'role'              => 'User',
            'password'          => Hash::make('password'),
            'remember_token'    => null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]]);

        DB::table('wallets')->insertOrIgnore([[
            'id'         => 4,
            'user_id'    => 4,
            'balance'    => 500.00,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        // ── Cart ─────────────────────────────────────────────────────────────
        DB::table('carts')->insertOrIgnore([[
            'id'         => 4,
            'user_id'    => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        // Initial quantity = 5 — both concurrent requests will read this value
        // in the unsafe scenario and race to overwrite it
        DB::table('cart_items')->insertOrIgnore([
            ['cart_id' => 4, 'product_id' => 1, 'quantity' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);

    }
}
