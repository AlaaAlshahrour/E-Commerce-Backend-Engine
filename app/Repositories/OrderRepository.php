<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Collection;

class OrderRepository
{
    public function getUserOrders(int $userId)
    {
        return Order::query()
            ->where('user_id', $userId)
            ->with('orderItems.product:id,name,photo_url')
            ->latest()
            ->get()
            ->map(fn($order) => $this->formatOrder($order));
    }

    public function getOrderById(int $orderId, int $userId)
    {
        return Order::query()
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->with('orderItems.product:id,name,photo_url')
            ->first();
    }


    public function createOrderUnsafe(User $user, Cart $cart, $totalAmount, array $data, Collection $cartItems): Order
    {

        /** @var Order $order */
        $order = $user->orders()->create([
            'status' => 'pending',
            'total_amount' => $totalAmount,
            'shipping_address' => $data['shipping_address'],
            'payment_status' => 'pending',
        ]);

        $orderItems = $cartItems->map(fn($item) => [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->product->price,
        ])->toArray();

        $order->orderItems()->createMany($orderItems);

        foreach ($cartItems as $item) {
            $item->product->inventory()->decrement('quantity', $item->quantity);
        }

        $cart->cartItems()->delete();

        return $order->load('orderItems.product:id,name,photo_url');

    }
    public function createOrderSafe(User $user, Cart $cart, $totalAmount, array $data, Collection $cartItems, Collection $lockedInventories): Order
    {

        /** @var Order $order */
        $order = $user->orders()->create([
            'status' => 'pending',
            'total_amount' => $totalAmount,
            'shipping_address' => $data['shipping_address'],
            'payment_status' => 'pending',
        ]);

        $orderItems = $cartItems->map(fn($item) => [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->product->price,
        ])->toArray();

        $order->orderItems()->createMany($orderItems);

        foreach ($cartItems as $item) {
            $inventory = $lockedInventories->get($item->product_id);
            $inventory->quantity -= $item->quantity;
            $inventory->save();
        }

        $cart->cartItems()->delete();

        return $order->load('orderItems.product:id,name,photo_url');

    }
    public function updateStatus(Order $order, string $status): Order
    {
        $order->status =  $status;
        $order->save();
        return $order->fresh();
    }

    private function formatOrder(Order $order): array
    {
        return [
            'order_id' => $order->id,
            'status' => $order->status,
            'total_amount' => (float)$order->total_amount,
            'shipping_address' => $order->shipping_address,
            'payment_status' => $order->payment_status,
            'created_at' => $order->created_at,
            'items' => $order->orderItems->map(fn($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'photo_url' => $item->product->photo_url,
                'quantity' => $item->quantity,
                'unit_price' => (float)$item->unit_price,
                'subtotal' => (float)$item->quantity * $item->unit_price,
            ]),
        ];
    }
}
