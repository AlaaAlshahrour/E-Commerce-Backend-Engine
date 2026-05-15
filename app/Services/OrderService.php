<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Repositories\CartRepository;
use App\Repositories\OrderRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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


    public function updateStatus(int $orderId, string $status): array
    {
        $order = $this->orderRepository->getOrderById($orderId, Auth::id());

        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        $allowedTransitions = [
            'pending' => ['Processing', 'Canceled'],
            'Processing' => ['Completed', 'Canceled'],
            'Completed' => [],
            'Canceled' => [],
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


    public function checkoutUnsafe(array $data): array
    {
        $user = Auth::user();
        $cart = $user->cart;

        if ($this->isCartEmpty($cart)) {
            return ['success' => false, 'message' => 'Cart is empty'];
        }


        /** @var Wallet $wallet */
        $wallet = $user->wallet;

        if ($this->isWalletUnvalid($wallet)) {
            return ['success' => false, 'message' => 'Wallet not found or inactive'];
        }

        $cartItems = $cart->cartItems()->with('product.inventory')->get();
        $productIds = $cartItems->pluck('product_id')->sort()->values();


        $inventories = Inventory::whereIn('product_id', $productIds)
            ->orderBy('product_id')
            ->get()
            ->keyBy('product_id');
        sleep(1);
        $unavailable = collect();
        foreach ($cartItems as $item) {
            $inventory = $inventories->get($item->product_id);
            $stock = $inventory ? $inventory->quantity : 0;

            if ($stock < $item->quantity) {
                $unavailable->push([
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'requested' => $item->quantity,
                    'available' => $stock,
                ]);
            }
        }

        if ($unavailable->isNotEmpty()) {
            return [
                'success' => false,
                'message' => 'Some products are out of stock',
                'data' => $unavailable->map(fn($item) => [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'requested' => $item->quantity,
                    'available' => $item->product->inventory?->quantity ?? 0,
                ]),
            ];
        }
        $amount = $cartItems->sum(fn($item) => $item->quantity * $item->product->price);

        if ($wallet->balance < $amount) {
            return [
                'success' => false,
                'message' => 'Insufficient wallet balance',
                'data' => [
                    'required' => $amount,
                    'available' => $wallet->balance,
                    'shortage' => $amount - $wallet->balance,
                ],
            ];
        }

        $order = $this->orderRepository->createOrder($user, $cart, $amount, $data, $cartItems,$inventories);


        $transaction = $this->makeTransaction($wallet, $amount, $order);


        $order->update(['payment_status' => 'paid']);


        return [
            'success' => true,
            'message' => 'Payment completed successfully',
            'data' => [
                'order' => $order,
                'transaction' => $transaction,
                'wallet_balance' => $wallet->fresh()->balance,
            ],
        ];

    }




    public function checkoutSafe(array $data): array
    {
        $user = Auth::user();
        $perUserLock = Cache::lock("checkout:user:{$user->id}");// previnting the same user to checkout twice.
        if (!$perUserLock->get()) {
            return ['success' => false, 'message' => 'Checkout in progress'];
        }
        try {
            $res = DB::transaction(function () use ($data, $perUserLock, $user, $inventoryLock) {

                // Validate Cart
                $cart = $user->cart;

                if ($this->isCartEmpty($cart)) {
                    return ['success' => false, 'message' => 'Cart is empty'];
                }

                $cartItems = $cart->cartItems()->with('product.inventory')->get();

                // Validate Wallet
                /** @var Wallet $wallet */
                $wallet = $user->wallet()->lockForUpdate()->first();

                if ($this->isWalletUnvalid($wallet)) {
                    return ['success' => false, 'message' => 'Wallet not found or inactive'];
                }

                // Check Inventory
                $productIds = $cartItems->pluck('product_id')->sort()->values();

                $inventories = Inventory::whereIn('product_id', $productIds)
                    ->orderBy('product_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('product_id');


                $unavailable = collect();
                foreach ($cartItems as $item) {
                    $inventory = $inventories->get($item->product_id);
                    $stock = $inventory ? $inventory->quantity : 0;

                    if ($stock < $item->quantity) {
                        $unavailable->push([
                            'product_id' => $item->product_id,
                            'product_name' => $item->product->name,
                            'requested' => $item->quantity,
                            'available' => $stock,
                        ]);
                    }
                }

                if ($unavailable->isNotEmpty()) {
                    return [
                        'success' => false,
                        'message' => 'Some products are out of stock',
                        'data' => $unavailable,
                    ];
                }

                // Validate user has amount.
                $amount = $cartItems->sum(fn($item) => $item->quantity * $item->product->price);

                if ($wallet->balance < $amount) {
                    return [
                        'success' => false,
                        'message' => 'Insufficient wallet balance',
                        'data' => [
                            'required' => $amount,
                            'available' => $wallet->balance,
                            'shortage' => $amount - $wallet->balance,
                        ],
                    ];
                }

                // Create the order.
                $order = $this->orderRepository->createOrder($user, $cart, $amount, $data, $cartItems, $inventories);
                $perUserLock->release();

                $transaction = $this->makeTransaction($wallet, $amount, $order);


                $order->update(['payment_status' => 'paid']);


                return [
                    'success' => true,
                    'message' => 'Payment completed successfully',
                    'data' => [
                        'order' => $order,
                        'transaction' => $transaction,
                        'wallet_balance' => $wallet->fresh()->balance,
                    ],
                ];
            });
            return $res;
        } finally {
            $perUserLock->release();
        }
    }


    /**
     * @param Wallet $wallet
     * @param $amount
     * @param Order $order
     * @return \Illuminate\Database\Eloquent\Model
     */
    function makeTransaction(Wallet $wallet, $amount, Order $order): \Illuminate\Database\Eloquent\Model
    {
        $balanceBefore = $wallet->balance;
        $wallet->decrement('balance', $amount);
        $balanceAfter = $wallet->balance;


        $transaction = $wallet->transactions()->create([
            'order_id' => $order->id,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'type' => 'payment',
            'status' => 'completed',
        ]);
        return $transaction;
    }

    /**
     * @param mixed $cart
     * @return bool
     */
    public function isCartEmpty(mixed $cart): bool
    {
        return !$cart || !$cart->cartItems()->exists();
    }

    /**
     * @param Wallet $wallet
     * @return bool
     */
    function isWalletUnvalid(Wallet $wallet): bool
    {
        return !$wallet || !$wallet->is_active;
    }

}
{
    $user = Auth::user();
    $perUserLock = Cache::lock("checkout:user:{$user->id}");// previnting the same user to checkout twice.
    if (!$perUserLock->get()) {
        return ['success' => false, 'message' => 'Checkout in progress'];
    }
    try {
        $res = DB::transaction(function () use ($data, $perUserLock, $user, $inventoryLock) {
            // Create the order.
            $perUserLock->release();
            // return success result
        });
        return $res;
    } finally {
        $perUserLock->release();
    }
}
