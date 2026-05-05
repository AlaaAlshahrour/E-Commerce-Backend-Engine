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

    public function addProductsToCartBatch(Cart $cart, array $cartProducts): void
    {
        DB::transaction(function () use ($cart, $cartProducts) {
            $cart->increment('cart_count', count($cartProducts));
            DB::table('cart_products')->insert($cartProducts);
        });
    }

    public function addProductToCart(Cart $cart, int $storeProductId, int $quantity): void
    {
        $cart->increment('cart_count');
        DB::table('cart_products')->insert([
            'cart_id' => $cart->id,
            'store_product_id' => $storeProductId,
            'amount_needed' => $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function getCartProducts(Cart $cart, $onlyUnavailable = false)
    {
        $lang = app()->getLocale();
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
                'storeProduct:id,store_id,product_id,price,quantity,sold_quantity,description_'.$lang.',main_image',
                'storeProduct.store:id,name_'.$lang,
                'storeProduct.product:id,category_id,name_'.$lang,
                'storeProduct.product.favoritedByUsers' => function ($query) {
                    $query->where('user_id', auth()->id());
                },
                'storeProduct.product.category:id,name_'.$lang,
            ])
            ->get()
            ->map(function ($cartProduct) use ($lang, &$total_price) {
                $storeProduct = $cartProduct->storeProduct;
                $isFavorite = $storeProduct->product->favoritedByUsers->isNotEmpty() ? 1 : 0;
                $order_amount = $cartProduct->amount_needed;
                $availableStock = $storeProduct->quantity;

                $message = $availableStock == 0
                    ? __('messages.no_stock_available')
                    : ($availableStock < $order_amount
                        ? __('messages.only_available', ['quantity' => $availableStock])
                        : __('messages.available_now'));

                if ($availableStock >= $order_amount) {
                    $total_price += $order_amount * $storeProduct->price;
                }
                $description = $storeProduct->{'description_'.$lang};
                $productName = $storeProduct->product->{'name_'.$lang};
                $storeName = $storeProduct->store->{'name_'.$lang};
                $categoryName = $storeProduct->product->category->{'name_'.$lang};

                $mainUrl = Storage::url($storeProduct->main_image);

                return [
                    'store_id' => $storeProduct->store->id,
                    'store_name' => $storeName,
                    'order_quantity' => $cartProduct->amount_needed,
                    'store_product_id' => $storeProduct->id,
                    'price' => $storeProduct->price,
                    'quantity' => $storeProduct->quantity,
                    'description' => $description,
                    'product_id' => $storeProduct->product->id,
                    'product_name' => $productName,
                    'category_id' => $storeProduct->product->category_id,
                    'category_name' => $categoryName,
                    'main_image' => asset($mainUrl),
                    'is_favorite' => $isFavorite,
                    'message' => $message,
                ];
            });

        return
            [
                'products' => $mappedProducts,
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
