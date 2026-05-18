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
    private int $completedOrders  = 0;
    private int $canceledOrders   = 0;
    private int $pendingOrders    = 0;
    private int $processingOrders = 0;
    public function process(string $date): array
    {
        $executionStartTime = microtime(true);

        // ════════════════════════════════════════════════════════════
        //  قياسات الذاكرة — البداية
        //  false = الذاكرة الفعلية التي يستخدمها PHP لتخزين البيانات
        //  true  = الذاكرة المُخصَّصة من نظام التشغيل (صفحات 4KB)
        // ════════════════════════════════════════════════════════════
        $mem_start_real = memory_get_usage(false);
        $mem_start_alloc = memory_get_usage(true);

        Log::info('========== Chunked Sales Processing Started ==========');
        Log::info("Processing date: {$date}");
        Log::info(sprintf(
            'Memory at START — Real: %.4f MB | Allocated: %.4f MB',
            $mem_start_real / 1024 / 1024,
            $mem_start_alloc / 1024 / 1024
        ));

        $dayStart = Carbon::parse($date)->startOfDay();
        $dayEnd = Carbon::parse($date)->endOfDay();

        Order::where('created_at', '>=', $dayStart)
            ->where('created_at', '<', $dayEnd)
            ->with('orderItems.product')
            ->chunkById(1000, function ($orders) {
                $this->processBatch($orders);
            });

        $mem_end_real = memory_get_usage(false);
        $mem_end_alloc = memory_get_usage(true);

        $peak_real = memory_get_peak_usage(false);
        $peak_alloc = memory_get_peak_usage(true);

        $delta_real = $mem_end_real - $mem_start_real;
        $delta_alloc = $mem_end_alloc - $mem_start_alloc;

        $executionTime = round(microtime(true) - $executionStartTime, 4);

        // ════════════════════════════════════════════════════════════
        //  تسجيل كل القياسات في الـ Log
        // ════════════════════════════════════════════════════════════
        Log::info('========== Chunked Processing Completed ==========');
        Log::info("Total Orders: {$this->totalOrders}");
        Log::info("Total Revenue: {$this->totalRevenue}");
        Log::info("Execution Time: {$executionTime}s");
        Log::info('--- Memory Statistics (Comprehensive) ---');
        Log::info(sprintf('START  — Real: %.4f MB | Allocated: %.4f MB',
            $mem_start_real / 1024 / 1024,
            $mem_start_alloc / 1024 / 1024));
        Log::info(sprintf('END    — Real: %.4f MB | Allocated: %.4f MB',
            $mem_end_real / 1024 / 1024,
            $mem_end_alloc / 1024 / 1024));
        Log::info(sprintf('PEAK   — Real: %.4f MB | Allocated: %.4f MB  ← الأهم للتقرير',
            $peak_real / 1024 / 1024,
            $peak_alloc / 1024 / 1024));
        Log::info(sprintf('DELTA  — Real: %.4f MB | Allocated: %.4f MB',
            $delta_real / 1024 / 1024,
            $delta_alloc / 1024 / 1024));
        Log::info("Batches Processed: {$this->batchCounter}");

        return [
            'total_orders' => $this->totalOrders,
            'total_revenue' => $this->totalRevenue,
            'execution_time' => $executionTime,
            'batches_count' => $this->batchCounter,
            'batches_metrics' => $this->batchesMetrics,
            'orders_data' => $this->ordersData,

            'memory_stats' => [
                // النقطة المرجعية — ما كانت عليه الذاكرة قبل أي معالجة
                'start_real_mb' => round($mem_start_real / 1024 / 1024, 4),
                'start_alloc_mb' => round($mem_start_alloc / 1024 / 1024, 4),

                // الذاكرة عند انتهاء المعالجة
                'end_real_mb' => round($mem_end_real / 1024 / 1024, 4),
                'end_alloc_mb' => round($mem_end_alloc / 1024 / 1024, 4),

                // الذروة — أعلى قيمة سُجِّلت خلال التنفيذ
                // peak_real_mb هو الرقم الحقيقي لأقصى استخدام للبيانات
                'peak_real_mb' => round($peak_real / 1024 / 1024, 4),
                // peak_alloc_mb هو ما يظهر في Task Manager / top
                'peak_alloc_mb' => round($peak_alloc / 1024 / 1024, 4),

                // الفرق الصافي = كم استهلك الـ processor زيادةً
                'delta_real_mb' => round($delta_real / 1024 / 1024, 4),
                'delta_alloc_mb' => round($delta_alloc / 1024 / 1024, 4),
            ],

            // للتوافق مع الكود القديم (يُبقي peak_memory و memory_used تعمل)
            'peak_memory' => round($peak_alloc / 1024 / 1024, 4),
            'memory_used' => round($delta_real / 1024 / 1024, 4),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function processBatch($orders): void
    {
        $this->batchCounter++;

        // قياس الذاكرة الفعلية قبل وبعد كل batch
        $mem_before_real = memory_get_usage(false);
        $mem_before_alloc = memory_get_usage(true);
        $batchStartTime = microtime(true);

        Log::info("Processing Batch #{$this->batchCounter} — Size: {$orders->count()} orders");

        foreach ($orders as $order) {
            $this->totalOrders++;
            $this->totalRevenue += $order->total_amount;
            $this->ordersData[] = $this->formatOrder($order);
        }

        $batchExecutionTime = round(microtime(true) - $batchStartTime, 4);
        $mem_after_real = memory_get_usage(false);
        $mem_after_alloc = memory_get_usage(true);

        $this->batchesMetrics[] = [
            'batch_number' => $this->batchCounter,
            'orders_count' => $orders->count(),
            'execution_time' => $batchExecutionTime,

            // القياسات القديمة (للتوافق مع blade الحالي)
            'memory_before' => round($mem_before_alloc / 1024 / 1024, 4),
            'memory_after' => round($mem_after_alloc / 1024 / 1024, 4),

            // القياسات الجديدة (الفعلية)
            'memory_before_real_mb' => round($mem_before_real / 1024 / 1024, 4),
            'memory_after_real_mb' => round($mem_after_real / 1024 / 1024, 4),
            'memory_delta_real_mb' => round(($mem_after_real - $mem_before_real) / 1024 / 1024, 4),
        ];

        Log::info(sprintf(
            'Batch #%d done — Time: %.4fs | Real: %.4f→%.4f MB (Δ%.4f) | Alloc: %.4f→%.4f MB',
            $this->batchCounter,
            $batchExecutionTime,
            $mem_before_real / 1024 / 1024,
            $mem_after_real / 1024 / 1024,
            ($mem_after_real - $mem_before_real) / 1024 / 1024,
            $mem_before_alloc / 1024 / 1024,
            $mem_after_alloc / 1024 / 1024
        ));
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
