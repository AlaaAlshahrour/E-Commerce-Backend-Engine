<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartRepository
{
    public function createCart(int $userId): Cart
    {
        return Cart::create(['user_id' => $userId]);
    }


    public function getProduct(int $productId)
    {
        return Product::with('inventory')->find($productId);
    }

    public function addProductToCart(Cart $cart, int $productId, int $quantity): void
    {
//        $cart->cartItems()->create(['product_id' => $productId, 'quantity' => $quantity]);
        DB::table('cart_items')->insert([
            'cart_id'    => $cart->id,
            'product_id' => $productId,
            'quantity'   => $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    }

    public function getCartProducts(Cart $cart)
    {
        $total_price = 0;

        $mappedProducts = CartItem::query()
            ->where('cart_id', $cart->id)
            ->with([
                'product:id,category_id,name,description,price,photo_url',
                'product.category:id,name',
                'product.inventory:id,product_id,quantity',
            ])
            ->get()
            ->map(function ($cartItem) use (&$total_price) {
                $product        = $cartItem->product;
                $order_amount   = $cartItem->quantity;
                $availableStock = optional($product->inventory)->quantity ?? 0;

                $message = $availableStock == 0
                    ? 'No stock available'
                    : ($availableStock < $order_amount
                        ? "Only {$availableStock} available"
                        : 'Available now');

                if ($availableStock >= $order_amount) {
                    $total_price += $order_amount * $product->price;
                }

                return [
                    'product_id'     => $product->id,
                    'product_name'   => $product->name,
                    'description'    => $product->description,
                    'price'          => $product->price,
                    'photo_url'      => $product->photo_url,
                    'category_id'    => $product->category_id,
                    'category_name'  => $product->category->name,
                    'order_quantity' => $order_amount,
                    'stock'          => $availableStock,
                    'message'        => $message,
                ];
            });

        return [
            'products'    => $mappedProducts,
            'total_price' => $total_price,
        ];
    }

    public function deleteAll(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->cart->cartItems()->delete();
        });
    }
    public function deleteProducts(Cart $cart, array $productIds): void
    {
        $cart->cartItems()->whereIn('product_id', $productIds)->delete();
    }
    public function updateProductQuantity(Cart $cart, int $productId, int $quantity): bool
    {
        return $cart->cartItems()
            ->where('product_id', $productId)
            ->update(['quantity' => $quantity]);
    }

}
