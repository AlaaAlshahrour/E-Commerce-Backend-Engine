<?php

namespace App\Processors\DailySales;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NormalSalesProcessor
{
    private float $executionStartTime;

    private int $totalOrders = 0;

    private float $totalRevenue = 0;

    private array $ordersData = [];

    public function process(string $date): array
    {
        $this->executionStartTime = microtime(true);

        $mem_start_real = memory_get_usage(false);
        $mem_start_alloc = memory_get_usage(true);

        Log::info('========== Normal Sales Processing Started ==========');
        Log::info("Processing date: {$date}");
        Log::info(sprintf(
            'Memory at START — Real: %.4f MB | Allocated: %.4f MB',
            $mem_start_real / 1024 / 1024,
            $mem_start_alloc / 1024 / 1024
        ));

        $dayStart = Carbon::parse($date)->startOfDay();
        $dayEnd = Carbon::parse($date)->endOfDay();

        $mem_before_get_real = memory_get_usage(false);
        $mem_before_get_alloc = memory_get_usage(true);

        Log::info(sprintf(
            'Memory BEFORE get() — Real: %.4f MB | Allocated: %.4f MB',
            $mem_before_get_real / 1024 / 1024,
            $mem_before_get_alloc / 1024 / 1024
        ));

        $orders = Order::where('created_at', '>=', $dayStart)
            ->where('created_at', '<', $dayEnd)
            ->with('orderItems.product')
            ->get();

        $mem_after_get_real = memory_get_usage(false);
        $mem_after_get_alloc = memory_get_usage(true);

        Log::info(sprintf(
            'Memory AFTER get() — Real: %.4f MB | Allocated: %.4f MB | Delta-Real: %.4f MB',
            $mem_after_get_real / 1024 / 1024,
            $mem_after_get_alloc / 1024 / 1024,
            ($mem_after_get_real - $mem_before_get_real) / 1024 / 1024
        ));
        Log::info("Orders loaded into memory: {$orders->count()}");

        if ($orders->count() > 100000) {
            Log::warning('Normal processing skipped: count > 100000');

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

        $mem_before_loop_real = memory_get_usage(false);
        $mem_before_loop_alloc = memory_get_usage(true);

        foreach ($orders as $order) {
            $this->totalOrders++;
            $this->totalRevenue += $order->total_amount;
            $this->ordersData[] = $this->formatOrder($order);
        }

        $mem_end_real = memory_get_usage(false);
        $mem_end_alloc = memory_get_usage(true);

        // الذروة الحقيقية — أعلى نقطة وصل إليها PHP خلال كامل التنفيذ
        $peak_real = memory_get_peak_usage(false);
        $peak_alloc = memory_get_peak_usage(true);

        $delta_total_real = $mem_end_real - $mem_start_real;   // إجمالي ما استُهلك
        $delta_get_real = $mem_after_get_real - $mem_before_get_real;  // تكلفة get() وحده
        $delta_loop_real = $mem_end_real - $mem_before_loop_real;       // تكلفة الـ foreach

        $executionTime = round(microtime(true) - $this->executionStartTime, 4);

        Log::info('========== Normal Processing Completed ==========');
        Log::info("Total Orders: {$this->totalOrders}");
        Log::info("Total Revenue: {$this->totalRevenue}");
        Log::info("Execution Time: {$executionTime}s");
        Log::info('--- Memory Statistics (Comprehensive) ---');
        Log::info(sprintf('START       — Real: %.4f MB | Alloc: %.4f MB',
            $mem_start_real / 1024 / 1024, $mem_start_alloc / 1024 / 1024));
        Log::info(sprintf('BEFORE get()— Real: %.4f MB | Alloc: %.4f MB',
            $mem_before_get_real / 1024 / 1024, $mem_before_get_alloc / 1024 / 1024));
        Log::info(sprintf('AFTER  get()— Real: %.4f MB | Alloc: %.4f MB | Δ(get): %.4f MB  ← تكلفة تحميل البيانات',
            $mem_after_get_real / 1024 / 1024,
            $mem_after_get_alloc / 1024 / 1024,
            $delta_get_real / 1024 / 1024));
        Log::info(sprintf('AFTER  loop — Real: %.4f MB | Alloc: %.4f MB | Δ(loop): %.4f MB',
            $mem_end_real / 1024 / 1024,
            $mem_end_alloc / 1024 / 1024,
            $delta_loop_real / 1024 / 1024));
        Log::info(sprintf('PEAK (real) — %.4f MB  ← الرقم الحقيقي للمقارنة',
            $peak_real / 1024 / 1024));
        Log::info(sprintf('PEAK (alloc)— %.4f MB  ← ما يظهر في OS',
            $peak_alloc / 1024 / 1024));
        Log::info(sprintf('DELTA total — Real: %.4f MB | Alloc: %.4f MB',
            $delta_total_real / 1024 / 1024,
            ($mem_end_alloc - $mem_start_alloc) / 1024 / 1024));

        return [
            'total_orders' => $this->totalOrders,
            'total_revenue' => $this->totalRevenue,
            'execution_time' => $executionTime,
            'orders_data' => $this->ordersData,
            'skipped' => false,

            'memory_stats' => [
                // نقاط قياس مرتّبة زمنياً
                'start_real_mb' => round($mem_start_real / 1024 / 1024, 4),
                'start_alloc_mb' => round($mem_start_alloc / 1024 / 1024, 4),

                'before_get_real_mb' => round($mem_before_get_real / 1024 / 1024, 4),
                'before_get_alloc_mb' => round($mem_before_get_alloc / 1024 / 1024, 4),

                // تكلفة get() = الرقم الأهم للمقارنة مع Batch
                'after_get_real_mb' => round($mem_after_get_real / 1024 / 1024, 4),
                'after_get_alloc_mb' => round($mem_after_get_alloc / 1024 / 1024, 4),
                'get_cost_real_mb' => round($delta_get_real / 1024 / 1024, 4),

                'end_real_mb' => round($mem_end_real / 1024 / 1024, 4),
                'end_alloc_mb' => round($mem_end_alloc / 1024 / 1024, 4),

                // الذروة — القيمة الأهم
                'peak_real_mb' => round($peak_real / 1024 / 1024, 4),
                'peak_alloc_mb' => round($peak_alloc / 1024 / 1024, 4),

                // الفروقات الصافية
                'delta_total_real_mb' => round($delta_total_real / 1024 / 1024, 4),
                'delta_get_real_mb' => round($delta_get_real / 1024 / 1024, 4),
                'delta_loop_real_mb' => round($delta_loop_real / 1024 / 1024, 4),
            ],

            'peak_memory' => round($peak_alloc / 1024 / 1024, 4),
            'memory_used' => round($delta_total_real / 1024 / 1024, 4),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────

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
