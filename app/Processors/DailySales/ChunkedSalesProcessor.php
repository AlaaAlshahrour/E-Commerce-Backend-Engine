<?php

namespace App\Processors\DailySales;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ChunkedSalesProcessor
{

    private int $totalOrders = 0;

    private float $totalRevenue = 0;

    private array $ordersData = [];

    private array $batchesMetrics = [];

    private int $batchCounter = 0;

    public function process(string $date): array
    {
        $executionStartTime = microtime(true);
        $initialMemory = memory_get_usage(false);

        Log::info('========== Chunked Sales Processing Started ==========');
        Log::info("Processing date: {$date}");

        $dayStart = Carbon::parse($date)->startOfDay();
        $dayEnd = Carbon::parse($date)->endOfDay();

        Order::where('created_at', '>=', $dayStart)
            ->where('created_at', '<', $dayEnd)
            ->with('orderItems.product')
            ->chunkById(1000, function ($orders) {
                $this->processBatch($orders);
            });

        $executionTime = round(microtime(true) - $executionStartTime, 4);
        $peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 4);
        $memoryUsed = round((memory_get_usage(false) - $initialMemory) / 1024 / 1024, 4);

        Log::info('========== Chunked Processing Completed ==========');
        Log::info("Total Orders: {$this->totalOrders}");
        Log::info("Total Revenue: {$this->totalRevenue}");
        Log::info("Execution Time: {$executionTime}s");
        Log::info("Peak Memory: {$peakMemory}MB");
        Log::info("Memory Used: {$memoryUsed}MB");
        Log::info("Batches Processed: {$this->batchCounter}");

        return [
            'total_orders' => $this->totalOrders,
            'total_revenue' => $this->totalRevenue,
            'execution_time' => $executionTime,
            'peak_memory' => $peakMemory,
            'memory_used' => $memoryUsed,
            'batches_count' => $this->batchCounter,
            'batches_metrics' => $this->batchesMetrics,
            'orders_data' => $this->ordersData,
        ];
    }

    private function processBatch($orders): void
    {
        $this->batchCounter++;
        $memoryBefore = round(memory_get_usage(false) / 1024 / 1024, 4);
        $batchStartTime = microtime(true);

        Log::info("Processing Batch #{$this->batchCounter}");
        Log::info("Batch Size: {$orders->count()} orders");

        foreach ($orders as $order) {
            $this->totalOrders++;
            $this->totalRevenue += $order->total_amount;

            // Keep only first 50 orders in memory
            if (count($this->ordersData) < 50) {
                $this->ordersData[] = $this->formatOrder($order);
            }
        }

        $batchExecutionTime = round(microtime(true) - $batchStartTime, 4);
        $memoryAfter = round(memory_get_usage(false) / 1024 / 1024, 4);

        $this->batchesMetrics[] = [
            'batch_number' => $this->batchCounter,
            'orders_count' => $orders->count(),
            'execution_time' => $batchExecutionTime,
            'memory_before' => $memoryBefore,
            'memory_after' => $memoryAfter,
        ];

        Log::info(
            "Batch #{$this->batchCounter} completed in {$batchExecutionTime}s, "
            ."Memory: {$memoryBefore}MB → {$memoryAfter}MB"
        );
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
