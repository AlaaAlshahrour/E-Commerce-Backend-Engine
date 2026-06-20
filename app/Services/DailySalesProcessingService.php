<?php

namespace App\Services;

use App\Enums\ProcessingMode;
use App\Processors\DailySales\ChunkedSalesProcessor;
use App\Processors\DailySales\NormalSalesProcessor;

class DailySalesProcessingService
{
    public function __construct(
        private ChunkedSalesProcessor $chunkedProcessor,
        private NormalSalesProcessor $normalProcessor,
    ) {}

    /**
     * Process daily sales based on the specified mode
     *
     * @return array<string, mixed>
     */
    public function process(string $date, ProcessingMode $mode): array
    {
        return match ($mode) {
            ProcessingMode::Batch => $this->processBatch($date),
            ProcessingMode::Normal => $this->processNormal($date),
            ProcessingMode::Compare => $this->processCompare($date),
        };
    }

    /**
     * Process using batch/chunked approach
     */
    private function processBatch(string $date): array
    {
        $result = $this->chunkedProcessor->process($date);

        return [
            'mode' => ProcessingMode::Batch->value,
            'date' => $date,
            'batch_result' => $result,
        ];
    }

    /**
     * Process using normal (get all) approach
     */
    private function processNormal(string $date): array
    {
        $result = $this->normalProcessor->process($date);

        return [
            'mode' => ProcessingMode::Normal->value,
            'date' => $date,
            'normal_result' => $result,
        ];
    }

    /**
     * Process and compare both batch and normal approaches
     */
    private function processCompare(string $date): array
    {

        $batchResult = $this->chunkedProcessor->process($date);

        $normalResult = $this->normalProcessor->process($date);

        // Skip comparison if normal processing was skipped
        if ($normalResult['skipped'] ?? false) {
            return [
                'mode' => ProcessingMode::Compare->value,
                'date' => $date,
                'normal_result' => $normalResult,
                'batch_result' => null,
                'comparison' => null,
                'normal_skipped' => true,
            ];
        }

        // Calculate comparison metrics
        $comparison = [
            'memory_reduction_percent' => round(
                (($normalResult['peak_memory'] - $batchResult['peak_memory']) / $normalResult['peak_memory']) * 100,
                2
            ),
            'speed_improvement_percent' => round(
                (($normalResult['execution_time'] - $batchResult['execution_time']) / $normalResult['execution_time']) * 100,
                2
            ),
            'normal_execution_time' => $normalResult['execution_time'],
            'batch_execution_time' => $batchResult['execution_time'],
            'normal_peak_memory' => $normalResult['peak_memory'],
            'batch_peak_memory' => $batchResult['peak_memory'],
        ];

        // Calculate additional batch statistics
        $batchStats = $this->calculateBatchStatistics($batchResult);

        return [
            'mode' => ProcessingMode::Compare->value,
            'date' => $date,
            'normal_result' => $normalResult,
            'batch_result' => $batchResult,
            'comparison' => $comparison,
            'batch_stats' => $batchStats,
            'normal_skipped' => false,
        ];
    }

    /**
     * Calculate additional batch statistics
     */
    private function calculateBatchStatistics(array $batchResult): array
    {
        $batchesMetrics = $batchResult['batches_metrics'] ?? [];

        if (empty($batchesMetrics)) {
            return [
                'average_batch_memory' => 0,
                'largest_batch_memory' => 0,
                'smallest_batch_memory' => 0,
                'batch_count' => 0,
                'batch_size' => 0,
            ];
        }

        $memoryDeltas = array_column($batchesMetrics, 'memory_delta_real_mb');

        return [
            'average_batch_memory' => round(array_sum($memoryDeltas) / count($memoryDeltas), 4),
            'largest_batch_memory' => round(max($memoryDeltas), 4),
            'smallest_batch_memory' => round(min($memoryDeltas), 4),
            'batch_count' => count($batchesMetrics),
            'batch_size' => $batchesMetrics[0]['orders_count'] ?? 0,
        ];
    }
}
