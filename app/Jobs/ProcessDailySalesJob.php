<?php

namespace App\Jobs;

use App\Models\DailySalesReport;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDailySalesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    protected string $date;

    public function __construct(?string $date = null)
    {
        $this->date = $date
            ? Carbon::parse($date)->toDateString()
            : Carbon::yesterday()->toDateString();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $targetDate = $this->date;

        // قياس الزمن الحقيقي
        $executionStart = microtime(true);

        // قياس الذاكرة قبل التنفيذ
        $initialMemory = memory_get_usage(true);

        $exportStartTime = Carbon::now();

        Log::info('========== Daily Sales Job Started ==========');
        Log::info("Processing date: {$targetDate}");
        Log::info("Started at: {$exportStartTime}");

        // منع تكرار التقارير
        $existingReport = DailySalesReport::where('date', $targetDate)->first();

        if ($existingReport) {
            Log::warning("Report already exists for {$targetDate}");
            return;
        }

        $totalOrders = 0;
        $totalRevenue = 0;
        $ordersData = [];
        $chunkCounter = 0;

        $dayStart = Carbon::parse($targetDate)->startOfDay();
        $dayEnd = Carbon::parse($targetDate)->endOfDay();

        Order::where('created_at', '>=', $dayStart)
            ->where('created_at', '<', $dayEnd)
            ->with('orderItems.product')
            ->chunkById(1000, function ($orders) use (
                &$totalOrders,
                &$totalRevenue,
                &$ordersData,
                &$chunkCounter
            ) {

                $chunkCounter++;

                $chunkMemoryBefore = memory_get_usage(false);

                Log::info("Processing Chunk #{$chunkCounter}");
                Log::info("Chunk Size: {$orders->count()} orders");

                foreach ($orders as $order) {

                    $totalOrders++;

                    $totalRevenue += $order->total_amount;

                    // الاحتفاظ بعدد محدود فقط داخل الذاكرة
                    if (count($ordersData) < 200) {

                        $items = [];
                        foreach ($order->orderItems as $item) {
                            $items[] = [
                                'product_name' => $item->product->name,
                                'quantity' => $item->quantity,
                                'unit_price' => $item->unit_price,
                                'subtotal' => $item->quantity * $item->unit_price,
                            ];
                        }

                        $ordersData[] = [
                            'id' => $order->id,
                            'status' => $order->status,
                            'payment_status' => $order->payment_status,
                            'total_amount' => $order->total_amount,
                            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                            'items' => $items,
                        ];
                    }
                }

                $chunkMemoryAfter = memory_get_usage(false);

                Log::info(
                    "Chunk #{$chunkCounter} Memory Usage: ".
                    round(($chunkMemoryAfter - $chunkMemoryBefore) / 1024 / 1024, 5)
                    .' MB'
                );

                Log::info(
                    'Current Total Memory Usage: '.
                    round(memory_get_usage(true) / 1024 / 1024, 5)
                    .' MB'
                );
            });

        Log::info('Daily sales processed successfully');
        Log::info("Total Orders: {$totalOrders}");
        Log::info("Total Revenue: {$totalRevenue}");

        // إنشاء PDF
        $pdfData = [
            'date' => $targetDate,
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'orders' => $ordersData,
        ];

        $pdf = Pdf::loadView('pdf.daily-sales-report', $pdfData);

        $pdfPath = "public/daily-reports/daily-sales-{$targetDate}.pdf";

        Storage::put($pdfPath, $pdf->output());

        $exportEndTime = Carbon::now();

        // حساب الزمن الكلي
        $executionEnd = microtime(true);

        $executionTime = round($executionEnd - $executionStart, 5);

        // حساب استهلاك الذاكرة النهائي
        $finalMemory = memory_get_usage(true);

        $memoryUsed = round(
            ($finalMemory - $initialMemory) / 1024 / 1024,
            5
        );

        Log::info('========== Performance Metrics ==========');
        Log::info("Execution Time: {$executionTime} seconds");
        Log::info("Memory Used: {$memoryUsed} MB");
        Log::info("Peak Memory Usage: " . round(memory_get_peak_usage(true) / 1024 / 1024, 5) . " MB");
        Log::info("Chunks Processed: {$chunkCounter}");

        Log::info("Export completed at: {$exportEndTime}");

        DailySalesReport::create([
            'date' => $targetDate,
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'pdf_path' => $pdfPath,
            'export_start_time' => $exportStartTime,
            'export_end_time' => $exportEndTime,
        ]);

        Log::info('Daily sales report created successfully');
        Log::info('========== Job Finished ==========');
    }
}
