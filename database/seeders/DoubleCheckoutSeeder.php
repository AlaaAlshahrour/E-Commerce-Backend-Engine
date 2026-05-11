<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * DoubleCheckoutSeeder
 * ====================
 * Scenario: Same user fires two checkout requests simultaneously.
 *
 * UNSAFE outcome  → both succeed, wallet is double-charged, double orders created.
 * SAFE outcome    → second request is rejected with "Checkout in progress".
 *
 * Run:
 *   php artisan db:seed --class=DoubleCheckoutSeeder
 *
 * Credentials:
 *   email:    double@example.com
 *   password: password
 */
class DoubleCheckoutSeeder extends Seeder
{
    public function run(): void
    {
        // ── Products ────────────────────────────────────────────────────────
        DB::table('categories')->insertOrIgnore([
            ['id' => 1, 'name' => 'Electronics', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('products')->insertOrIgnore([
            ['id' => 1, 'name' => 'Wireless Headphones', 'description' => 'Over-ear BT headphones.', 'price' => 99.99,  'photo_url' => null, 'category_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'USB-C Charger 65W',   'description' => 'GaN fast charger.',        'price' => 34.99,  'photo_url' => null, 'category_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Cotton T-Shirt',       'description' => 'Organic cotton tee.',      'price' => 19.99,  'photo_url' => null, 'category_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Enough stock so inventory is never the failure reason
        DB::table('inventories')->insertOrIgnore([
            ['product_id' => 1, 'quantity' => 100, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 2, 'quantity' => 100, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => 3, 'quantity' => 100, 'created_at' => now(), 'updated_at' => now()],
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

        // ── Wallet ($999 — enough to pay twice without balance being the issue)
        DB::table('wallets')->insertOrIgnore([[
            'id'         => 4,
            'user_id'    => 4,
            'balance'    => 999.00,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        DB::table('transactions')->insert([[
            'wallet_id'      => 4,
            'order_id'       => null,
            'amount'         => 999.00,
            'balance_before' => 0.00,
            'balance_after'  => 999.00,
            'type'           => 'deposit',
            'status'         => 'completed',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]]);

        // ── Cart ─────────────────────────────────────────────────────────────
        DB::table('carts')->insertOrIgnore([[
            'id'         => 4,
            'user_id'    => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        // products 1, 2, 3 — total = 99.99 + 34.99 + 19.99 = $154.97 per checkout
        // Wallet $999 covers ~6 checkouts, so balance is never the blocker
        DB::table('cart_items')->insertOrIgnore([
            ['cart_id' => 4, 'product_id' => 1, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['cart_id' => 4, 'product_id' => 2, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['cart_id' => 4, 'product_id' => 3, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('✅  DoubleCheckoutSeeder done.');
        $this->command->info('   email: double@example.com | password: password');
        $this->command->info('   wallet: $999.00 | cart: products [1, 2, 3] | total per checkout: $154.97');
    }
}
