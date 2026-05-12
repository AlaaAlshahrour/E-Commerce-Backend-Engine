<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\User;
use App\Repositories\CartRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CartService
{
    protected $cartRepository;

    public function __construct(CartRepository $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }

    public function addProductToCart(int $product_id, int $quantity): array
    {

        // first race condition
        // same user adds the same product from different devices
        // we will solve it by applying DB unique constraint
        $user = Auth::user();
        $cart = $user->cart ?? $this->cartRepository->createCart($user->id);

        $product = $this->cartRepository->getProduct($product_id);

        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        $availableStock = $product->inventory?->quantity ?? 0;

        if ($availableStock === 0) {
            return ['success' => false, 'message' => 'No stock available'];
        }

        if ($availableStock < $quantity) {
            return ['success' => false, 'message' => "Only {$availableStock} available"];
        }

        $alreadyInCart = $cart->cartItems()->where('product_id', $product_id)->exists();
        if ($alreadyInCart) {
            return ['success' => false, 'message' => 'Product already in cart'];
        }

        try {
            $this->cartRepository->addProductToCart($cart, $product_id, $quantity);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {  // Duplicate entry
                return ['success' => false, 'message' => 'Product already in cart'];
            }
            return ['success' => false, 'message' => 'An Error Occurred'];

        }
        return ['success' => true, 'message' => 'Product added to cart'];
    }

    public function getAllProductsInCart(User $user): array
    {
        $cart = $user->cart;

        if (!$cart) {
            return ['success' => false, 'message' => 'Cart is empty'];
        }

        $cartData = $this->cartRepository->getCartProducts($cart);

        if ($cartData['products']->isEmpty()) {
            return ['success' => false, 'message' => 'Cart is empty'];
        }

        return ['success' => true, 'message' => 'Products Retrieved', 'data' => $cartData];
    }

    public function deleteAll(): void
    {
        $user = Auth::user();
        $this->cartRepository->deleteAll($user);
    }

    public function deleteProducts(array $productIds): array
    {
        $user = Auth::user();
        $cart = $user->cart;

        if (!$cart) {
            return ['success' => false, 'message' => 'Cart is empty'];
        }

        $existingIds = $cart->cartItems()
            ->whereIn('product_id', $productIds)
            ->pluck('product_id')
            ->toArray();

        $notFoundIds = array_values(array_diff($productIds, $existingIds));

        if (!empty($existingIds)) {
            $this->cartRepository->deleteProducts($cart, $existingIds);
        }

        return [
            'success' => true,
            'message' => 'Operation completed',
            'deleted_ids' => $existingIds,
            'not_found_ids' => $notFoundIds,
        ];
    }

    public function updateProductQuantityUnsafe(int $productId, int $quantity): array
    {
        // First Race Condition : user updates the same product in his cart from 2 devices
        // Since we are not incrementing the quantity but assigning it, using Pessimistic lock is not useful,
        // there is a missing update value, we will use Optimistic lock, assuming user wont update the quantity
        // and before saving the new quantity we check if there is someone who has updated the quantity while we were doing our business logic
        // in this way , we can tell user that his update failed.


        $user = Auth::user();
        $cart = $user->cart;

        if (!$cart) {
            return ['success' => false, 'message' => 'Cart is empty'];
        }

        $cartItem = $cart->cartItems()->where('product_id', $productId)->first();




        if (!$cartItem) {
            return ['success' => false, 'message' => 'Product not found in cart'];
        }

        $product = $this->cartRepository->getProduct($productId);
        $availableStock = $product->inventory?->quantity ?? 0;

        if ($availableStock === 0) {
            return ['success' => false, 'message' => 'No stock available'];
        }

        if ($quantity > $availableStock) {
            return [
                'success' => false,
                'message' => "Only {$availableStock} available",
            ];
        }

        $success = $this->cartRepository->updateProductQuantity($cart, $productId, $quantity);

        return ['success' => true, 'message' => 'Quantity updated successfully'];
    }

    public function updateProductQuantitySafe(int $productId, int $quantity): array
    {

        $user = Auth::user();
        $cart = $user->cart;
        $lock = Cache::lock("update_cart:$cart->id:$productId");
        try {

            if (!$lock->get())
                return ['success' => false, 'message' => 'Quantity updated From Another DeviceL'];

            if (!$cart) {
                return ['success' => false, 'message' => 'Cart is empty'];
            }

            $cartItem = CartItem::where('product_id', $productId)->where('cart_id', $cart->id)->lockForUpdate()->first();

            if (!$cartItem) {
                return ['success' => false, 'message' => 'Product not found in cart'];
            }

            $product = $this->cartRepository->getProduct($productId);
            $availableStock = $product->inventory?->quantity ?? 0;

            if ($availableStock === 0) {
                return ['success' => false, 'message' => 'No stock available'];
            }

            if ($quantity > $availableStock) {
                return [
                    'success' => false,
                    'message' => "Only {$availableStock} available",
                ];
            }
            $success = $this->cartRepository->updateProductQuantity($cart, $productId, $quantity);
            $lock->release();
            if (!$success) {
                return ['success' => false, 'message' => 'Quantity updated From Another Device'];
            }
            return ['success' => true, 'message' => 'Quantity updated successfully'];
        } finally {
            $lock->release();
        }

    }
}


