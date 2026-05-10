<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Jobs\GenerateInvoicePdfJob;
use Throwable;

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

    public function checkout(array $data): array
    {
        // Create order
        $createOrderResult = $this->createOrder($data);

        if (!$createOrderResult['success']) {
            return $createOrderResult;
        }

        $order = $createOrderResult['data'];
        $user = Auth::user();
        $wallet = $user->wallet;

        // Check if wallet exists and is active
        if (!$wallet || !$wallet->is_active) {
            return ['success' => false, 'message' => 'Wallet not found or inactive'];
        }

        // Check if wallet has sufficient balance
        if ($wallet->balance < $order->total_amount) {
            return [
                'success' => false,
                'message' => 'Insufficient wallet balance',
                'data' => [
                    'required' => $order->total_amount,
                    'available' => $wallet->balance,
                    'shortage' => $order->total_amount - $wallet->balance,
                ],
            ];
        }

        // Process payment via transaction
        try {
            $transaction = DB::transaction(function () use ($order, $wallet, $user) {
                $balanceBefore = $wallet->balance;
                $amount = $order->total_amount;

                // Deduct from wallet
                $wallet->decrement('balance', $amount);

                // Create transaction record
                $transaction = Transaction::create([
                    'wallet_id' => $wallet->id,
                    'order_id' => $order->id,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceBefore - $amount,
                    'type' => 'payment',
                    'status' => 'completed',
                ]);

                // Update order payment status
                Order::find($order->id)->update(['payment_status' => 'paid']);

                return $transaction;
            });

            // Reload order with updated payment status
            $updatedOrder = $this->getOrderById($order->id);
            GenerateInvoicePdfJob::dispatch($order->id);

            return [
                'success' => true,
                'message' => 'Payment completed successfully',
                'data' => [
                    'order' => $updatedOrder['data'],
                    'transaction' => $transaction,
                    'wallet_balance' => $wallet->fresh()->balance,
                ],
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage(),
            ];
        }
    }

}
