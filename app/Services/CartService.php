<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\CartRepository;
use Illuminate\Support\Facades\Auth;

class CartService
{
    protected $cartRepository;

    public function __construct(CartRepository $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }

    public function addProductToCart(int $product_id, int $quantity): array
    {
        $user = Auth::user();
        $cart = $user->cart ?? $this->cartRepository->createCart($user->id);

        $product = $this->cartRepository->getProduct($product_id);

        \Log::info('inventory: '.json_encode($product->inventory));
        \Log::info('quantity: '.$product->inventory?->quantity);
        \Log::info('requested: '.$quantity);
        if (! $product) {
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

        $this->cartRepository->addProductToCart($cart, $product_id, $quantity);

        return ['success' => true, 'message' => 'Product added to cart'];
    }

    public function getAllProductsInCart(User $user): array
    {
        $cart = $user->cart;

        if (! $cart) {
            return ['message' => 'Cart is empty'];
        }

        $cartData = $this->cartRepository->getCartProducts($cart);

        if ($cartData['products']->isEmpty()) {
            return ['message' => 'Cart is empty'];
        }

        return $cartData;
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

        if (! $cart) {
            return ['success' => false, 'message' => 'Cart is empty'];
        }

        $existingIds = $cart->cartItems()
            ->whereIn('product_id', $productIds)
            ->pluck('product_id')
            ->toArray();

        $notFoundIds = array_values(array_diff($productIds, $existingIds));

        if (! empty($existingIds)) {
            $this->cartRepository->deleteProducts($cart, $existingIds);
        }

        return [
            'success' => true,
            'message' => 'Operation completed',
            'deleted_ids' => $existingIds,
            'not_found_ids' => $notFoundIds,
        ];
    }

    public function updateProductQuantity(int $productId, int $quantity): array
    {
        $user = Auth::user();
        $cart = $user->cart;

        if (! $cart) {
            return ['success' => false, 'message' => 'Cart is empty'];
        }

        $cartItem = $cart->cartItems()->where('product_id', $productId)->first();

        if (! $cartItem) {
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

        $this->cartRepository->updateProductQuantity($cart, $productId, $quantity);

        return ['success' => true, 'message' => 'Quantity updated successfully'];
    }
}
