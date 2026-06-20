<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * AcidTestSeeder
 *
 * Single user with a cart and enough balance.
 * The checkout will be triggered with break-trans=true to simulate a
 * mid-transaction crash. ACID guarantees everything rolls back atomically.
 *
 * Demonstrates:
 *   UNSAFE → order row created, inventory decremented, wallet NOT charged
 *            (partial write — data is now incoherent)
 *   SAFE   → DB::transaction rolls everything back, DB is pristine
 *
 * Run: php artisan db:seed --class=AcidTestSeeder
 */
class AcidTestSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categories')->insertOrIgnore([
            ['id' => 1, 'name' => 'Electronics', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('products')->insertOrIgnore([[
            'id'          => 301,
            'name'        => 'ACID Test Product',
            'description' => 'Used to prove atomicity. Should never appear in a committed order.',
            'price'       => 49.99,
            'photo_url'   => null,
            'category_id' => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]]);

        DB::table('inventories')->insertOrIgnore([[
            'product_id' => 301,
            'quantity'   => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        // ── User ────────────────────────────────────────────────────────
        DB::table('users')->insertOrIgnore([[
            'id'                => 200,
            'name'              => 'ACID Tester',
            'email'             => 'acid@example.com',
            'email_verified_at' => now(),
            'role'              => 'User',
            'password'          => Hash::make('password'),
            'remember_token'    => null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]]);

        DB::table('wallets')->insertOrIgnore([[
            'id'         => 200,
            'user_id'    => 200,
            'balance'    => 300.00,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        DB::table('transactions')->insert([[
            'wallet_id'      => 200,
            'order_id'       => null,
            'amount'         => 300.00,
            'balance_before' => 0.00,
            'balance_after'  => 300.00,
            'type'           => 'deposit',
            'status'         => 'completed',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]]);

        DB::table('carts')->insertOrIgnore([[
            'id'         => 200,
            'user_id'    => 200,
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        DB::table('cart_items')->insertOrIgnore([[
            'cart_id'    => 200,
            'product_id' => 301,
            'quantity'   => 2,  // total = $99.98, well within $300 balance
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        $this->command->info('✅ AcidTestSeeder complete.');
        $this->command->info('   User: acid@example.com / password');
        $this->command->info('   Product 301 — quantity: 10, price: $49.99');
        $this->command->info('   Wallet: $300.00');
        $this->command->info('   Expected (unsafe + break): partial write — incoherent DB');
        $this->command->info('   Expected (safe   + break): full rollback — pristine DB');
    }
}
