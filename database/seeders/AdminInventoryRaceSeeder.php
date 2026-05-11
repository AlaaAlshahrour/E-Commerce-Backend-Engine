<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * AdminInventoryRaceSeeder
 * ========================
 * Scenario: Admin updates inventory quantity while a customer is
 *           simultaneously checking out the same product.
 *
 * THE RACE (unsafe):
 *   Inventory starts at qty=40.
 *   Admin reads 40, decides to set it to 60 (+20 restock).
 *   Customer buys 10 units at the same time.
 *   Admin blindly writes 60 → customer's 10-unit deduction is lost.
 *   Final qty = 60 instead of correct 50.
 *
 * UNSAFE OUTCOME:
 *   Checkout → 200  order created, inventory decremented to 30
 *   Admin    → 200  inventory overwritten to 60 (hides the sale)
 *   Final DB qty = 60 — 10 sold units vanished from records.
 *
 * SAFE OUTCOME:
 *   Checkout sets cache key product:active_purchases:301 = 10
 *   Admin hits safe endpoint → sees pendingPurchases=10 → blocked
 *   Returns: "Cannot update now — Product is being purchased."
 *   Admin retries after checkout completes → sets correct value.
 *
 * Run:
 *   php artisan db:seed --class=AdminInventoryRaceSeeder
 *
 * Credentials:
 *   buyer: buyer@example.com  / password  (role: User)
 *   admin: admin@example.com  / password  (role: Admin)
 *
 * Product: id=301, qty=40
 * Buyer cart: 10 units of product 301
 * Admin will try to set qty=60 simultaneously
 */
class AdminInventoryRaceSeeder extends Seeder
{
    const PRODUCT_ID  = 301;
    const BUYER_ID    = 30;
    const ADMIN_ID    = 31;
    const BUYER_WALLET = 30;
    const BUYER_CART  = 30;
    const INITIAL_QTY = 40;

    public function run(): void
    {
        // ── Category ─────────────────────────────────────────────────────
        DB::table('categories')->insertOrIgnore([
            ['id' => 1, 'name' => 'Electronics', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Product ───────────────────────────────────────────────────────
        DB::table('products')->insertOrIgnore([[
            'id'          => self::PRODUCT_ID,
            'name'        => 'Admin Race Product',
            'description' => 'Used to demonstrate admin inventory update vs checkout race.',
            'price'       => 29.99,
            'photo_url'   => null,
            'category_id' => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]]);

        // qty=40: buyer will purchase 10, admin will try to set 60
        // correct final qty after safe handling = 50 (admin adjusts for the sale)
        DB::table('inventories')->insertOrIgnore([[
            'product_id' => self::PRODUCT_ID,
            'quantity'   => self::INITIAL_QTY,
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        // ── Buyer (User) ──────────────────────────────────────────────────
        DB::table('users')->insertOrIgnore([
            [
                'id'                => self::BUYER_ID,
                'name'              => 'Race Buyer',
                'email'             => 'buyer@example.com',
                'email_verified_at' => now(),
                'role'              => 'User',
                'password'          => Hash::make('password'),
                'remember_token'    => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            // ── Admin ─────────────────────────────────────────────────────
            [
                'id'                => self::ADMIN_ID,
                'name'              => 'Inventory Admin',
                'email'             => 'admin@example.com',
                'email_verified_at' => now(),
                'role'              => 'Admin',
                'password'          => Hash::make('password'),
                'remember_token'    => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ]);

        // ── Buyer wallet — $500, well above 10 * $29.99 = $299.90 ────────
        DB::table('wallets')->insertOrIgnore([[
            'id'         => self::BUYER_WALLET,
            'user_id'    => self::BUYER_ID,
            'balance'    => 500.00,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        DB::table('transactions')->insert([[
            'wallet_id'      => self::BUYER_WALLET,
            'order_id'       => null,
            'amount'         => 500.00,
            'balance_before' => 0.00,
            'balance_after'  => 500.00,
            'type'           => 'deposit',
            'status'         => 'completed',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]]);

        // ── Buyer cart: 10 units of product 301 ───────────────────────────
        DB::table('carts')->insertOrIgnore([[
            'id'         => self::BUYER_CART,
            'user_id'    => self::BUYER_ID,
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        DB::table('cart_items')->insertOrIgnore([[
            'cart_id'    => self::BUYER_CART,
            'product_id' => self::PRODUCT_ID,
            'quantity'   => 10,   // buyer purchases 10 units
            'created_at' => now(),
            'updated_at' => now(),
        ]]);

        $this->command->info('✅  AdminInventoryRaceSeeder done.');
        $this->command->info('   buyer@example.com / password  — cart: 10x product 301 | wallet: $500');
        $this->command->info('   admin@example.com / password  — will try to set qty=60');
        $this->command->info('   Product 301 initial qty = 40');
        $this->command->info('');
        $this->command->info('   Expected safe final qty  = 40 (admin blocked, buyer decrements)');
        $this->command->info('   Expected unsafe final qty = 60 (admin overwrites buyers deduction)');
        $this->command->info('');
        $this->command->info('   ⚠️  Reset between runs:');
        $this->command->info('      UPDATE inventories SET quantity=40 WHERE product_id=301;');
        $this->command->info('      DELETE FROM cart_items WHERE product_id=301;');
        $this->command->info('      INSERT INTO cart_items ...; (or re-seed)');
    }
}
