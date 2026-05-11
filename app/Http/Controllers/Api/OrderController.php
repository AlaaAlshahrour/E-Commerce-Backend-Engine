<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

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
            'data'    => $result['data'],
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'shipping_address' => 'required|string|min:5',
        ]);

        $result = $this->orderService->checkoutSafe($request->only([
            'shipping_address',
        ]));

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
                'data'    => $result['data'] ?? [],
            ], 422);
        }

        return response()->json([
            'message' => $result['message'],
            'data'    => $result['data'],
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

    public function checkoutDoubleCheckout(Request $request)
    {
        return response()->json(
            $this->orderService->checkoutDoubleCheckoutCanHappen(
                $request->only('shipping_address')
            )
        );
    }



    public function checkoutSafe(Request $request)
    {
        return response()->json(
            $this->orderService->checkoutSafe(
                $request->only('shipping_address')
            )
        );
    }
}
