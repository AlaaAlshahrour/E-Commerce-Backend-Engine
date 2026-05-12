<?php

namespace App\Processors\DailySales;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NormalSalesProcessor
{
    private float $executionStartTime;

    private float $initialMemory;

    private int $totalOrders = 0;

    private float $totalRevenue = 0;

    private array $ordersData = [];

    public function process(string $date): array
    {
        $this->executionStartTime = microtime(true);
        $this->initialMemory = memory_get_usage(false);

        Log::info('========== Normal Sales Processing Started ==========');
        Log::info("Processing date: {$date}");

        $dayStart = Carbon::parse($date)->startOfDay();
        $dayEnd = Carbon::parse($date)->endOfDay();

        $orders = Order::where('created_at', '>=', $dayStart)
            ->where('created_at', '<', $dayEnd)
            ->with('orderItems.product')
            ->get();

        // Guard clause: Prevent processing if orders exceed 100k
        if ($orders->count() > 100000) {
            Log::warning(
                'Normal processing skipped: Orders count ('.$orders->count()
                .') exceeds maximum threshold of 100000'
            );

            return [
                'total_orders' => 0,
                'total_revenue' => 0,
                'execution_time' => 0,
                'peak_memory' => 0,
                'memory_used' => 0,
                'skipped' => true,
                'reason' => 'Orders count exceeds 100000',
                'orders_data' => [],
            ];
        }

        foreach ($orders as $order) {
            $this->totalOrders++;
            $this->totalRevenue += $order->total_amount;

            // Keep only first 50 orders in memory
            if (count($this->ordersData) < 50) {
                $this->ordersData[] = $this->formatOrder($order);
            }
        }

        $executionTime = round(microtime(true) - $this->executionStartTime, 4);
        $peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 4);
        $memoryUsed = round((memory_get_usage(false) - $this->initialMemory) / 1024 / 1024, 4);

        Log::info('========== Normal Processing Completed ==========');
        Log::info("Total Orders: {$this->totalOrders}");
        Log::info("Total Revenue: {$this->totalRevenue}");
        Log::info("Execution Time: {$executionTime}s");
        Log::info("Peak Memory: {$peakMemory}MB");
        Log::info("Memory Used: {$memoryUsed}MB");

        return [
            'total_orders' => $this->totalOrders,
            'total_revenue' => $this->totalRevenue,
            'execution_time' => $executionTime,
            'peak_memory' => $peakMemory,
            'memory_used' => $memoryUsed,
            'orders_data' => $this->ordersData,
            'skipped' => false,
        ];
    }

    private function formatOrder($order): array
    {
        $items = [];
        foreach ($order->orderItems as $item) {
            $items[] = [
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->quantity * $item->unit_price,
            ];
        }

        return [
            'id' => $order->id,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total_amount' => $order->total_amount,
            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
            'items' => $items,
        ];
    }
}
