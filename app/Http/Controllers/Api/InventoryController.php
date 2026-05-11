<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index()
    {
        $result = $this->inventoryService->getAll();

        if (isset($result['message'])) {
            return ResponseHelper::jsonResponse($result['message']);
        }

        return ResponseHelper::jsonResponse($result['data'], 201);
    }

    public function show(int $productId)
    {
        $result = $this->inventoryService->getByProductId($productId);

        if (isset($result['message'])) {
            return ResponseHelper::jsonResponse($result['message']);
        }
        return ResponseHelper::jsonResponse($result['data']);
    }

    public function update(int $productId, Request $request)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $result = $this->inventoryService->updateQuantityUnsafe($productId, $request->input('quantity'));

        if (!$result['success']) {
            return ResponseHelper::jsonResponse('', $result['message'], 404);
        }

        return ResponseHelper::jsonResponse($result['message']);
    }

    public function updateUnsafe(int $productId, Request $request)
    {
        $request->validate(['quantity' => 'required|integer|min:0']);

        $result = $this->inventoryService->updateQuantityUnsafe($productId, $request->input('quantity'));

        if (!$result['success']) {
            return ResponseHelper::jsonResponse('', $result['message'], 404);
        }
        return ResponseHelper::jsonResponse($result['data']?? [] ,$result['message']);


    }

    public function updateSafe(int $productId, Request $request)
    {
        $request->validate(['quantity' => 'required|integer|min:0']);

        $result = $this->inventoryService->updateQuantitySafe($productId, $request->input('quantity'));

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data'    => $result['data'] ?? [],
            ], 422);
        }


        return ResponseHelper::jsonResponse($result['data']?? [] ,$result['message']);
    }

}
