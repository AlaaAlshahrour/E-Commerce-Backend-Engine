<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * RaceRetrySeeder
 *
 * Sets up 5 users all racing to buy the same scarce product (qty = 3).
 * Only 3 should ever succeed. The remaining 2 should either:
 *   - (unsafe) oversell — quantity goes negative
 *   - (safe)   be rejected after retries exhaust — version conflict detected
 *
 * Run: php artisan db:seed --class=RaceRetrySeeder
 */
class RaceRetrySeeder extends Seeder
{
    public function run(): void
    {
        // ── Category ────────────────────────────────────────────────────────
        DB::table('categories')->insertOrIgnore([
            ['id' => 1, 'name' => 'Electronics', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Scarce product — only 3 units, 5 users want it ──────────────────
        DB::table('products')->insertOrIgnore([[
            'id'          => 201,
            'name'        => 'Limited Gaming Controller (3 Left)',
            'description' => 'Only 3 units in stock. Used to demonstrate retry + optimistic lock.',
            'price'       => 59.99,
            'photo_url'   => null,
            'category_id' => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]]);

        DB::table('inventories')->insertOrIgnore([[
            'product_id' => 201,
            'quantity'   => 3,   // ← only 3 units — exactly 3 of 5 users should succeed
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        // ── 5 competing users ────────────────────────────────────────────────
        $users = [];
        $wallets = [];
        $carts = [];
        $cartItems = [];

        for ($i = 1; $i <= 5; $i++) {
            $uid = 100 + $i; // user IDs 101–105

            $users[] = [
                'id'                => $uid,
                'name'              => "Racer {$i}",
                'email'             => "racer{$i}@example.com",
                'email_verified_at' => now(),
                'role'              => 'User',
                'password'          => Hash::make('password'),
                'remember_token'    => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];

            $wallets[] = [
                'id'         => $uid,
                'user_id'    => $uid,
                'balance'    => 500.00, // well above $59.99
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $carts[] = [
                'id'         => $uid,
                'user_id'    => $uid,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $cartItems[] = [
                'cart_id'    => $uid,
                'product_id' => 201,
                'quantity'   => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('users')->insertOrIgnore($users);
        DB::table('wallets')->insertOrIgnore($wallets);

        // Seed deposit transactions for each wallet
        $transactions = [];
        for ($i = 1; $i <= 5; $i++) {
            $uid = 100 + $i;
            $transactions[] = [
                'wallet_id'      => $uid,
                'order_id'       => null,
                'amount'         => 500.00,
                'balance_before' => 0.00,
                'balance_after'  => 500.00,
                'type'           => 'deposit',
                'status'         => 'completed',
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }
        DB::table('transactions')->insert($transactions);

        DB::table('carts')->insertOrIgnore($carts);
        DB::table('cart_items')->insertOrIgnore($cartItems);

        $this->command->info('✅ RaceRetrySeeder complete.');
        $this->command->info('   Product 201 — quantity: 3');
        $this->command->info('   Users: racer1@example.com … racer5@example.com (password: password)');
        $this->command->info('   Expected: 3 succeed, 2 fail (safe) | all 5 may succeed (unsafe = oversell)');
    }
}
