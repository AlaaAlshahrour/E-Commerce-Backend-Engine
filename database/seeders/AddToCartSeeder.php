<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * AddToCartSeeder
 * ===============
 * Scenario: Same user adds the same product to their cart from two devices
 *           at exactly the same time.
 *
 * THE RACE CONDITION (without fix):
 *   Both requests call $cart->cartItems()->where('product_id', ...)->exists()
 *   at the same time — both read FALSE (product not in cart yet).
 *   Both pass the check and both call INSERT into cart_items.
 *   The DB unique constraint on (cart_id, product_id) rejects the second
 *   INSERT with a duplicate key exception (SQLSTATE 23000).
 *   Because the app does not catch this exception, the second request
 *   returns HTTP 500 instead of a clean 422 error message.
 *
 * EXPECTED UNSAFE OUTCOME:
 *   Request 1 → 200  { message: "Product added to cart" }
 *   Request 2 → 500  (unhandled QueryException / duplicate entry)
 *
 * EXPECTED SAFE OUTCOME (after fix):
 *   Request 1 → 200  { message: "Product added to cart" }
 *   Request 2 → 422  { message: "Product already in cart" }
 *   (fix = wrap the insert in a try/catch for QueryException code 23000,
 *    or use insertOrIgnore, or add a DB-level advisory lock)
 *
 * Run:
 *   php artisan db:seed --class=AddToCartSeeder
 *
 * Credentials:
 *   email:    cart@example.com
 *   password: password
 *   product:  id=201, name="Race Condition Gadget", stock=50
 *
 * IMPORTANT — reset between runs:
 *   The test adds the product to the cart. Re-running without a fresh
 *   migration will always return "Product already in cart" for BOTH
 *   requests. Run:  php artisan migrate:fresh --seed  OR manually:
 *     DELETE FROM cart_items WHERE product_id = 201;
 */
class AddToCartSeeder extends Seeder
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

        // cart_items intentionally left EMPTY — both requests race to be first

        $this->command->info('✅  AddToCartSeeder done.');
        $this->command->info('   email: cart@example.com | password: password');
        $this->command->info('   product: id=201 "Race Condition Gadget" | stock=50');
        $this->command->info('   cart is EMPTY — both requests will race to add product 201');
        $this->command->info('');
        $this->command->info('   ⚠️  To re-run the test without full migrate:fresh:');
        $this->command->info('      DELETE FROM cart_items WHERE product_id = 201;');
    }
}
