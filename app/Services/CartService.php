<?php

namespace App\Services;

use App\Models\Cart;
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

    public function addProductToCart(int $store_id, int $product_id, int $quantity): array
    {
        $user = Auth::user();

        $cart = $user->cart ?? $this->cartRepository->createCart($user->id);

        $storeProduct = $this->cartRepository->getStoreProduct($store_id, $product_id);

        if ($storeProduct->quantity < $quantity) {
            return ['success' => false, 'message' => __('messages.not_enough_stock')];
        }

        $this->cartRepository->addProductToCart($cart, $storeProduct->id, $quantity);

        return ['success' => true, 'message' => __('messages.product_added_to_cart')];
    }
    public function getAllProductsInCart(User $user): array
    {
        $cart = $user->cart;

        if (! $cart) {
            return ['message' => __('messages.cart_empty')];
        }

        $cartData = $this->cartRepository->getCartProducts($cart);

        if (empty($cartData['products'])) {
            return ['message' => __('messages.cart_empty')];
        }

        return $cartData;
    }

    public function deleteAll()
    {
        $user = Auth::user();
        $this->cartRepository->deleteAll($user);
    }
}
