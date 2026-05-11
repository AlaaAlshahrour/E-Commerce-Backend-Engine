<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
    }

    public function index()
    {
        $result = $this->orderService->getUserOrders();

        if (!$result['success']) {
            return ResponseHelper::jsonResponse($result['message'], '', 404);
        }

        return response()->json(['data' => $result['data']]);
    }


    public function show(int $id)
    {
        $result = $this->orderService->getOrderById($id);

        if (!$result['success']) {
            return ResponseHelper::jsonResponse($result['message'], '', 404);
        }

        return response()->json(['data' => $result['data']]);
    }

    public function updateStatus(int $id, Request $request)
    {
        $request->validate([
            'status' => 'required|in:Processing,Canceled,Completed,pending',
        ]);

        $result = $this->orderService->updateStatus($id, $request->input('status'));

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => $result['data'],
        ]);
    }

    public function checkout(CheckoutRequest $request)
    {
        $data = $request->validated();
        $safe = $request->query('safe') == "1";

        $result = $safe ? $this->orderService->checkoutSafe($data)
            : $this->orderService->checkoutUnsafe($data);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
                'data' => $result['data'] ?? [],
            ], 422);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => $result['data'],
        ], 201);
    }


    public function checkoutUnsafe(Request $request)
    {
        return response()->json(
            $this->orderService->checkoutUnsafe(
                $request->only('shipping_address')
            )
        );
    }



}
