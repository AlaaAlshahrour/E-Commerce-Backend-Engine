<?php

namespace App\Jobs;

use App\Models\DailySalesReport;
use App\Models\Inventory;
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
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $yesterday = Carbon::yesterday()->toDateString();

        Log::info("Starting daily sales processing job for date: {$yesterday}");

        // التحقق من وجود تقرير سابق لليوم
        $existingReport = DailySalesReport::where('date', $yesterday)->first();
        if ($existingReport) {
            Log::info("Report already exists for {$yesterday}, skipping.");

            return;
        }

        $totalOrders = 0;
        $totalRevenue = 0.0;
        $processedItems = 0;
        $ordersData = [];

        // معالجة الطلبات على دفعات (100 طلب)
        Order::where('status', 'Completed')
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', Carbon::yesterday()->startOfDay())
            ->where('created_at', '<', Carbon::today()->startOfDay())
            ->with('orderItems.product')
            ->chunk(100, function ($orders) use (&$totalOrders, &$totalRevenue, &$processedItems, &$ordersData) {
                foreach ($orders as $order) {
                    $totalOrders++;
                    $totalRevenue += $order->total_amount;

                    // تحديث المخزون
                    foreach ($order->orderItems as $item) {
                        Inventory::where('product_id', $item->product_id)
                            ->decrement('quantity', $item->quantity);
                        $processedItems++;
                    }

                    // جمع بيانات الطلبات للـ PDF
                    $ordersData[] = [
                        'id' => $order->id,
                        'total_amount' => $order->total_amount,
                        'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                        'items' => $order->orderItems->map(function ($item) {
                            return [
                                'product_name' => $item->product->name,
                                'quantity' => $item->quantity,
                                'unit_price' => $item->unit_price,
                                'subtotal' => $item->quantity * $item->unit_price,
                            ];
                        })->toArray(),
                    ];
                }
            });

        // تسجيل في Log
        Log::info("Daily sales processed: {$totalOrders} orders, total revenue: {$totalRevenue}, processed items: {$processedItems}");

        // إنشاء PDF
        $pdfData = [
            'date' => $yesterday,
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'orders' => $ordersData,
        ];

        $pdf = Pdf::loadView('pdf.daily-sales-report', $pdfData);
        $pdfPath = "public/daily-reports/daily-sales-{$yesterday}.pdf";
        Storage::put($pdfPath, $pdf->output());

        // حفظ في الجدول
        DailySalesReport::create([
            'date' => $yesterday,
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'pdf_path' => $pdfPath,
        ]);

        Log::info("Daily sales report created and saved for {$yesterday}");
    }
}
