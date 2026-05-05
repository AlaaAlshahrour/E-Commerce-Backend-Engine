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

        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        $availableStock = $product->inventory->quantity ?? 0;

        if ($availableStock < $quantity) {
            return ['success' => false, 'message' => 'Not enough stock'];
        }

        // منع إضافة نفس المنتج مرتين
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

        if (!$cart) {
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
}
