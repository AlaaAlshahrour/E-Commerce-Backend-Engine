<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
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


    public function createOrder(int $userId, array $data, Collection $cartItems): Order
    {
        return DB::transaction(function () use ($userId, $data, $cartItems) {
            $totalAmount = $cartItems->sum(fn($item) => $item->quantity * $item->product->price);

            $order = Order::create([
                'user_id'          => $userId,
                'status'           => 'pending',
                'total_amount'     => $totalAmount,
                'shipping_address' => $data['shipping_address'],
                'payment_method'   => $data['payment_method'],
                'payment_status'   => 'pending',
            ]);

            $orderItems = $cartItems->map(fn($item) => [
                'order_id'   => $order->id,
                'product_id' => $item->product_id,
                'quantity'   => $item->quantity,
                'unit_price' => $item->product->price,
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            OrderItem::insert($orderItems);

            foreach ($cartItems as $item) {
                $item->product->inventory()->decrement('quantity', $item->quantity);
            }

            CartItem::where('cart_id', $cartItems->first()->cart_id)->delete();

            return $order->load('orderItems.product:id,name,photo_url');
        });
    }

    public function updateStatus(Order $order, string $status): Order
    {
        $order->update(['status' => $status]);
        return $order->fresh();
    }

    private function formatOrder(Order $order): array
    {
        return [
            'order_id'         => $order->id,
            'status'           => $order->status,
            'total_amount'     => (float) $order->total_amount,
            'shipping_address' => $order->shipping_address,
            'payment_method'   => $order->payment_method,
            'payment_status'   => $order->payment_status,
            'created_at'       => $order->created_at,
            'items'            => $order->orderItems->map(fn($item) => [
                'product_id'   => $item->product_id,
                'product_name' => $item->product->name,
                'photo_url'    => $item->product->photo_url,
                'quantity'     => $item->quantity,
                'unit_price'   => (float) $item->unit_price,
                'subtotal'     => (float) $item->quantity * $item->unit_price,
            ]),
        ];
    }
}
