<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function add(int $product_id, Request $request)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $result = $this->cartService->addProductToCart($product_id, $request->input('quantity'));

        if (!$result['success']) {
            return ResponseHelper::jsonResponse('', $result['message'], 422);
        }

        return ResponseHelper::jsonResponse('', $result['message'], 200);
    }

    public function getCartProducts()
    {
        $user = Auth::user();
        $result = $this->cartService->getAllProductsInCart($user);

        if (isset($result['message'])) {
            return ResponseHelper::jsonResponse($result['message']);
        }

        return response()->json([
            'data'        => $result['products'],
            'total_price' => (float) $result['total_price'],
        ]);
    }

    public function deleteAll()
    {
        $this->cartService->deleteAll();
        return ResponseHelper::jsonResponse('Cart cleared successfully');
    }
}
