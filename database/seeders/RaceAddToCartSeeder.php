<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RaceAddToCartSeeder extends Seeder
{
    const PRODUCT_ID = 201;
    const USER_ID    = 20;
    const WALLET_ID  = 20;
    const CART_ID    = 20;

    public function run(): void
    {
        // ── Category ─────────────────────────────────────────────────────
        DB::table('categories')->insertOrIgnore([
            ['id' => 1, 'name' => 'Electronics', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Product — enough stock so that is never the failure reason ───
        DB::table('products')->insertOrIgnore([[
            'id'          => self::PRODUCT_ID,
            'name'        => 'Race Condition Gadget',
            'description' => 'Used to demonstrate the add-to-cart duplicate race condition.',
            'price'       => 49.99,
            'photo_url'   => null,
            'category_id' => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]]);

        DB::table('inventories')->insertOrIgnore([[
            'product_id' => self::PRODUCT_ID,
            'quantity'   => 50,   // plenty — stock check must not be the failure
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        // ── User ──────────────────────────────────────────────────────────
        DB::table('users')->insertOrIgnore([[
            'id'                => self::USER_ID,
            'name'              => 'Cart Race Tester',
            'email'             => 'cart@example.com',
            'email_verified_at' => now(),
            'role'              => 'User',
            'password'          => Hash::make('password'),
            'remember_token'    => null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]]);

        DB::table('wallets')->insertOrIgnore([[
            'id'         => self::WALLET_ID,
            'user_id'    => self::USER_ID,
            'balance'    => 500.00,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        DB::table('transactions')->insert([[
            'wallet_id'      => self::WALLET_ID,
            'order_id'       => null,
            'amount'         => 500.00,
            'balance_before' => 0.00,
            'balance_after'  => 500.00,
            'type'           => 'deposit',
            'status'         => 'completed',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]]);

        // ── Cart — empty on purpose, the test will try to add product 201 ─
        DB::table('carts')->insertOrIgnore([[
            'id'         => self::CART_ID,
            'user_id'    => self::USER_ID,
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

 }
}
