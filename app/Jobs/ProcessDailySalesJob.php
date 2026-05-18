<?php

namespace App\Jobs;

use App\Enums\ProcessingMode;
use App\Models\DailySalesReport;
use App\Services\DailySalesProcessingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDailySalesJob implements ShouldQueue
{
    use Queueable;

    protected string $date;

    protected ProcessingMode $mode;

    public function __construct(?string $date = null, ?ProcessingMode $mode = null)
    {
        $this->date = $date
            ? Carbon::parse($date)->toDateString()
            : Carbon::yesterday()->toDateString();

        $this->mode = $mode ?? ProcessingMode::Batch;
    }

    public function handle(DailySalesProcessingService $processingService): void
    {
        $exportStartTime = Carbon::now();

        Log::info('========== Daily Sales Job Started ==========');
        Log::info("Processing date: {$this->date}");
        Log::info("Mode: {$this->mode->value}");
        Log::info("Started at: {$exportStartTime}");

        // Prevent duplicate reports
        $existingReport = DailySalesReport::where('date', $this->date)->first();

        if ($existingReport) {
            Log::warning("Report already exists for {$this->date}");

            return;
        }

        // Process sales data
        $result = $processingService->process($this->date, $this->mode);

        // Generate PDF and save report
        $this->generateAndSaveReport($result, $exportStartTime);

        Log::info('Daily sales report created successfully');
        Log::info('========== Job Finished ==========');
    }

    private function generateAndSaveReport(array $result, Carbon $exportStartTime): void
    {
        $pdfData = $this->preparePdfData($result);

        $pdf = Pdf::loadView('pdf.daily-sales-report', $pdfData);

        $pdfPath = "public/daily-reports/daily-sales-{$this->date}.pdf";

        Storage::put($pdfPath, $pdf->output());

        $exportEndTime = Carbon::now();

        $reportData = [
            'date' => $this->date,
            'processing_mode' => $this->mode->value,
            'total_orders' => $pdfData['total_orders'],
            'total_revenue' => $pdfData['total_revenue'],
            'pdf_path' => $pdfPath,
            'export_start_time' => $exportStartTime,
            'export_end_time' => $exportEndTime,
        ];

        DailySalesReport::create($reportData);

        Log::info("Report exported at: {$exportEndTime}");
    }

    private function preparePdfData(array $result): array
    {
        return match ($result['mode']) {
            ProcessingMode::Batch->value => $this->prepareBatchPdfData($result),
            ProcessingMode::Normal->value => $this->prepareNormalPdfData($result),
            ProcessingMode::Compare->value => $this->prepareComparePdfData($result),
        };
    }

    private function prepareBatchPdfData(array $result): array
    {
        $batchResult = $result['batch_result'];

        return [
            'date' => $this->date,
            'processing_mode' => ProcessingMode::Batch->value,
            'total_orders' => $batchResult['total_orders'],
            'total_revenue' => $batchResult['total_revenue'],
            'orders' => $batchResult['orders_data'],
            'performance_metrics' => [
                'execution_time' => $batchResult['execution_time'],
                'peak_memory' => $batchResult['peak_memory'],
                'memory_used' => $batchResult['memory_used'],
                'batches_count' => $batchResult['batches_count'],
            ],
            'batches_metrics' => $batchResult['batches_metrics'],
            'batch_timeline' => true,
            'order_stats' => $this->calculateOrderStats($batchResult['orders_data']),
        ];
    }

    private function prepareNormalPdfData(array $result): array
    {
        $normalResult = $result['normal_result'];

        return [
            'date' => $this->date,
            'processing_mode' => ProcessingMode::Normal->value,
            'total_orders' => $normalResult['total_orders'],
            'total_revenue' => $normalResult['total_revenue'],
            'orders' => $normalResult['orders_data'],
            'performance_metrics' => [
                'execution_time' => $normalResult['execution_time'],
                'peak_memory' => $normalResult['peak_memory'],
                'memory_used' => $normalResult['memory_used'],
            ],
            'batch_timeline' => false,
            'order_stats' => $this->calculateOrderStats($normalResult['orders_data']),
        ];
    }

    private function prepareComparePdfData(array $result): array
    {
        if ($result['normal_skipped']) {
            // If normal was skipped, show only batch
            return $this->prepareBatchPdfData(['batch_result' => $result['batch_result']]);
        }

        $normalResult = $result['normal_result'];
        $batchResult = $result['batch_result'];
        $comparison = $result['comparison'];
        $batchStats = $result['batch_stats'];

        // Prepare normal stats
        $normalStats = [
            'orders_processed' => $normalResult['total_orders'],
            'execution_time' => $normalResult['execution_time'],
            'peak_memory_real' => $normalResult['memory_stats']['peak_real_mb'] ?? $normalResult['peak_memory'],
            'peak_memory_allocated' => $normalResult['memory_stats']['peak_alloc_mb'] ?? $normalResult['peak_memory'],
            'memory_delta' => $normalResult['memory_stats']['delta_total_real_mb'] ?? $normalResult['memory_used'],
            'start_memory_real' => $normalResult['memory_stats']['start_real_mb'] ?? 0,
            'end_memory_real' => $normalResult['memory_stats']['end_real_mb'] ?? 0,
            'orders_loaded' => $normalResult['total_orders'],
            'status' => $normalResult['skipped'] ? 'Skipped' : 'Completed',
            'failed' => $normalResult['skipped'],
        ];

        // Prepare batch stats
        $batchStatsFull = [
            'orders_processed' => $batchResult['total_orders'],
            'execution_time' => $batchResult['execution_time'],
            'peak_memory_real' => $batchResult['memory_stats']['peak_real_mb'] ?? $batchResult['peak_memory'],
            'peak_memory_allocated' => $batchResult['memory_stats']['peak_alloc_mb'] ?? $batchResult['peak_memory'],
            'memory_delta' => $batchResult['memory_stats']['delta_real_mb'] ?? $batchResult['memory_used'],
            'start_memory_real' => $batchResult['memory_stats']['start_real_mb'] ?? 0,
            'end_memory_real' => $batchResult['memory_stats']['end_real_mb'] ?? 0,
            'orders_loaded' => $batchResult['total_orders'],
            'batch_count' => $batchStats['batch_count'],
            'batch_size' => $batchStats['batch_size'],
            'average_batch_memory' => $batchStats['average_batch_memory'],
            'largest_batch_memory' => $batchStats['largest_batch_memory'],
            'smallest_batch_memory' => $batchStats['smallest_batch_memory'],
            'status' => 'Completed',
            'failed' => false,
        ];

        return [
            'date' => $this->date,
            'processing_mode' => ProcessingMode::Compare->value,
            'total_orders' => $batchResult['total_orders'],
            'total_revenue' => $batchResult['total_revenue'],
            'orders' => $batchResult['orders_data'],
            'performance_metrics' => [
                'execution_time' => $batchResult['execution_time'],
                'peak_memory' => $batchResult['peak_memory'],
                'memory_used' => $batchResult['memory_used'],
                'batches_count' => $batchResult['batches_count'],
            ],
            'batches_metrics' => $batchResult['batches_metrics'],
            'batch_timeline' => true,
            'benchmark_comparison' => [
                'normal_execution_time' => $comparison['normal_execution_time'],
                'batch_execution_time' => $comparison['batch_execution_time'],
                'speed_improvement_percent' => $comparison['speed_improvement_percent'],
                'normal_peak_memory' => $comparison['normal_peak_memory'],
                'batch_peak_memory' => $comparison['batch_peak_memory'],
                'memory_reduction_percent' => $comparison['memory_reduction_percent'],
            ],
            'normal_stats' => $normalStats,
            'batch_stats' => $batchStatsFull,
            'comparison' => $comparison,
            'batch_details' => $batchResult['batches_metrics'],
            'order_stats' => $this->calculateOrderStats($batchResult['orders_data']),
        ];
    }
    private function calculateOrderStats(array $ordersData): array
    {
        $statuses = array_column($ordersData, 'status');
        $amounts  = array_column($ordersData, 'total_amount');

        $completed = count(array_filter($statuses, fn($s) => $s === 'Completed'));
        $canceled  = count(array_filter($statuses, fn($s) => $s === 'Canceled'));
        $pending   = count(array_filter($statuses, fn($s) => $s === 'pending'));
        $processing = count(array_filter($statuses, fn($s) => $s === 'Processing'));
        $total     = count($ordersData);

        return [
            'completed_orders'  => $completed,
            'canceled_orders'   => $canceled,
            'pending_orders'    => $pending,
            'processing_orders' => $processing,
            'total_cost'        => round(array_sum($amounts), 2),
            'average_order'     => $total > 0 ? round(array_sum($amounts) / $total, 2) : 0,
        ];
    }
}
