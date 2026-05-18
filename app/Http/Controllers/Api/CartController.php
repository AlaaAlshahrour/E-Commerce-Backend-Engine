<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    protected CartService $cartService;

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
            return ResponseHelper::jsonResponse('', $result['message'], 422,false);
        }

        return ResponseHelper::jsonResponse('', $result['message'], 200);
    }

    public function getCartProducts()
    {
        $user = Auth::user();
        $result = $this->cartService->getAllProductsInCart($user);

        if (!$result['success']) {
            return ResponseHelper::jsonResponse([], $result['message'], 404, false);
        }

        return ResponseHelper::jsonResponse($result['data'], $result['message']);

    }

    public function deleteAll()
    {
        $this->cartService->deleteAll();
        return ResponseHelper::jsonResponse(null, 'Cart cleared successfully');
    }

    public function deleteProducts(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer',
        ]);

        $result = $this->cartService->deleteProducts($request->input('product_ids'));

        if (!$result['success']) {
            ResponseHelper::jsonResponse(null, $result['message'], 422, false);
        }
        $data = ['deleted_ids' => $result['deleted_ids'],
            'not_found_ids' => $result['not_found_ids']];

        return ResponseHelper::jsonResponse($data, $result['message']);
    }

    public function update(Request $request, int $productId)
    {

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'safe' => 'sometimes'
        ]);
        $safe = $request->query('safe') == "1";
//        return $safe;
        Log::info($safe);
        $result = $safe ? $this->cartService
            ->updateProductQuantitySafe(
                $productId,
                $request->quantity
            )
            :
            $this->cartService->updateProductQuantityUnsafe(
                $productId,
                $request->quantity
            );

        if (!$result['success']) {
            return ResponseHelper::jsonResponse(null, $result['message'], 422,false);
        }

        return ResponseHelper::jsonResponse(null, $result['message']);
    }


}
