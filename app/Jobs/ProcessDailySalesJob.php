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
        ];
    }
}
