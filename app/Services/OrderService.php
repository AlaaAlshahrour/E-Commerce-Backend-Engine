<?php

namespace App\Services;

use App\Models\Order;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\Auth;

class OrderService
{
    public function __construct(protected OrderRepository $orderRepository) {}

    public function getUserOrders(): array
    {
        $orders = $this->orderRepository->getUserOrders(Auth::id());

        if ($orders->isEmpty()) {
            return ['success' => false, 'message' => 'No orders found'];
        }

        return ['success' => true, 'data' => $orders];
    }

    public function getOrderById(int $orderId): array
    {
        $order = $this->orderRepository->getOrderById($orderId, Auth::id());

        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        return ['success' => true, 'data' => $order];
    }

    public function createOrder(array $data): array
    {
        $user = Auth::user();
        $cart = $user->cart;

        if (!$cart) {
            return ['success' => false, 'message' => 'Cart is empty'];
        }

        $cartItems = $cart->cartItems()->with('product.inventory')->get();

        if ($cartItems->isEmpty()) {
            return ['success' => false, 'message' => 'Cart is empty'];
        }

        // تحقق من توفر المخزون لكل المنتجات
        $unavailable = $cartItems->filter(
            fn($item) => ($item->product->inventory?->quantity ?? 0) < $item->quantity
        );

        if ($unavailable->isNotEmpty()) {
            return [
                'success' => false,
                'message' => 'Some products are out of stock',
                'data'    => $unavailable->map(fn($item) => [
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product->name,
                    'requested'    => $item->quantity,
                    'available'    => $item->product->inventory?->quantity ?? 0,
                ]),
            ];
        }

        $order = $this->orderRepository->createOrder($user->id, $data, $cartItems);

        return ['success' => true, 'message' => 'Order created successfully', 'data' => $order];
    }

    public function updateStatus(int $orderId, string $status): array
    {
        $order = $this->orderRepository->getOrderById($orderId, Auth::id());

        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        $allowedTransitions = [
            'pending'    => ['Processing', 'Canceled'],
            'Processing' => ['Completed', 'Canceled'],
            'Completed'  => [],
            'Canceled'   => [],
        ];

        if (!in_array($status, $allowedTransitions[$order->status] ?? [])) {
            return [
                'success' => false,
                'message' => "Cannot transition from {$order->status} to {$status}",
            ];
        }

        $updated = $this->orderRepository->updateStatus($order, $status);

        return ['success' => true, 'message' => 'Status updated successfully', 'data' => $updated];
    }

}
