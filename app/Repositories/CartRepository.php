<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CartRepository
{
    public function createCart(int $userId): Cart
    {
        return Cart::create(['user_id' => $userId]);
    }

    public function getStoreProduct(int $storeId, int $productId)
    {
        return DB::table('store_products')
            ->where('store_id', $storeId)
            ->where('product_id', $productId)
            ->first();
    }


    public function addProductToCart(Cart $cart, int $storeProductId, int $quantity): void
    {
        $cart->increment('cart_count');
        DB::table('cart_items')->insert([
            'cart_id' => $cart->id,
            'store_product_id' => $storeProductId,
            'amount_needed' => $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function getCartProducts(Cart $cart, $onlyUnavailable = false)
    {
        $total_price = 0;

        $query = CartItem::query()
            ->where('cart_id', $cart->id);

        if ($onlyUnavailable) {
            $query->whereHas('storeProduct', function ($query) {
                $query->whereColumn('quantity', '>=', 'amount_needed');
            });
        }

        $mappedProducts = $query
            ->with([
                'storeProduct:id,store_id,product_id,price,quantity,sold_quantity,description,main_image',
                'storeProduct.store:id,name',
                'storeProduct.product:id,category_id,name',
                'storeProduct.product.favoritedByUsers' => function ($query) {
                    $query->where('user_id', auth()->id());
                },
                'storeProduct.product.category:id,name',
            ])
            ->get()
            ->map(function ($cartProduct) use (&$total_price) {
                $storeProduct = $cartProduct->storeProduct;
                $isFavorite = $storeProduct->product->favoritedByUsers->isNotEmpty() ? 1 : 0;
                $order_amount = $cartProduct->amount_needed;
                $availableStock = $storeProduct->quantity;

                $message = $availableStock == 0
                    ? 'No stock available'
                    : ($availableStock < $order_amount
                        ? "Only {$availableStock} available"
                        : 'Available now');

                if ($availableStock >= $order_amount) {
                    $total_price += $order_amount * $storeProduct->price;
                }

                $mainUrl = Storage::url($storeProduct->main_image);

                return [
                    'store_id'         => $storeProduct->store->id,
                    'store_name'       => $storeProduct->store->name,
                    'order_quantity'   => $cartProduct->amount_needed,
                    'store_product_id' => $storeProduct->id,
                    'price'            => $storeProduct->price,
                    'quantity'         => $storeProduct->quantity,
                    'description'      => $storeProduct->description,
                    'product_id'       => $storeProduct->product->id,
                    'product_name'     => $storeProduct->product->name,
                    'category_id'      => $storeProduct->product->category_id,
                    'category_name'    => $storeProduct->product->category->name,
                    'main_image'       => asset($mainUrl),
                    'is_favorite'      => $isFavorite,
                    'message'          => $message,
                ];
            });

        return [
            'products'    => $mappedProducts,
            'total_price' => $total_price,
        ];
    }

    public function deleteAll(User $user)
    {
        DB::transaction(function () use ($user) {
            $cart = $user->cart;
            $cart->cartProducts()->delete();
            $cart->cart_count = 0;
            $cart->save();
        });
    }
}
