<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Sales Report - {{ $date }}</title>
    <style>
        * { margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #333; }
        .container { width: 100%; padding: 20px; }
        h1 { text-align: center; font-size: 24px; margin-bottom: 20px; color: #1a1a1a; }
        h2 { font-size: 14px; margin-top: 20px; margin-bottom: 10px; color: #444; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        h3 { font-size: 12px; margin-top: 15px; margin-bottom: 8px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f0f0f0; border: 1px solid #999; padding: 8px; text-align: left; font-weight: bold; }
        td { border: 1px solid #ddd; padding: 6px; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .summary-grid { display: table; width: 100%; margin-top: 10px; }
        .summary-item { display: table-cell; width: 25%; padding: 8px; border-right: 1px solid #ddd; }
        .summary-item:last-child { border-right: none; }
        .summary-label { font-weight: bold; color: #666; font-size: 10px; }
        .summary-value { font-size: 14px; color: #1a1a1a; margin-top: 4px; }
        .metric-row { display: table; width: 100%; margin-top: 5px; }
        .metric-col { display: table-cell; width: 50%; padding: 5px; }
        .metric-label { font-weight: bold; color: #666; font-size: 11px; }
        .metric-value { color: #1a1a1a; font-size: 12px; margin-top: 2px; }
        .positive { color: #27ae60; }
        .batch-item { margin-bottom: 8px; padding: 8px; background-color: #fafafa; border-left: 3px solid #3498db; }
        .orders-table { margin-top: 15px; }
        .page-break { page-break-after: always; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <h1>Daily Sales Report</h1>

        <!-- Executive Summary -->
        <h2>Executive Summary</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Report Date</div>
                <div class="summary-value">{{ $date }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Processing Mode</div>
                <div class="summary-value">
                    @if($processing_mode === 'batch')
                        Batch Processing
                    @elseif($processing_mode === 'normal')
                        Normal Processing
                    @elseif($processing_mode === 'compare')
                        Compare Mode
                    @endif
                </div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Orders</div>
                <div class="summary-value">{{ number_format($total_orders) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Revenue</div>
                <div class="summary-value">${{ number_format($total_revenue, 2) }}</div>
            </div>
        </div>

        <!-- Final Performance Metrics -->
        <h2>Final Performance Metrics</h2>
        <table>
            <tr>
                <th>Metric</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Execution Time</td>
                <td>{{ $performance_metrics['execution_time'] }}s</td>
            </tr>
            <tr>
                <td>Peak Memory Usage</td>
                <td>{{ $performance_metrics['peak_memory'] }} MB</td>
            </tr>
            <tr>
                <td>Memory Used</td>
                <td>{{ $performance_metrics['memory_used'] }} MB</td>
            </tr>
            @if($batch_timeline)
            <tr>
                <td>Chunks Processed</td>
                <td>{{ $performance_metrics['batches_count'] }}</td>
            </tr>
            @endif
        </table>

        <!-- Batch Timeline (only for Batch and Compare modes) -->
        @if($batch_timeline && isset($batches_metrics) && !empty($batches_metrics))
        <h2>Batch Timeline</h2>
        <table>
            <thead>
                <tr>
                    <th>Batch #</th>
                    <th>Orders Count</th>
                    <th>Time (s)</th>
                    <th>Memory Before (MB)</th>
                    <th>Memory After (MB)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($batches_metrics as $batch)
                <tr>
                    <td>{{ $batch['batch_number'] }}</td>
                    <td>{{ $batch['orders_count'] }}</td>
                    <td>{{ $batch['execution_time'] }}</td>
                    <td>{{ $batch['memory_before'] }}</td>
                    <td>{{ $batch['memory_after'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Benchmark Comparison (only for Compare mode) -->
        @if(isset($benchmark_comparison) && !empty($benchmark_comparison))
        <h2>Benchmark Comparison: Batch vs Normal</h2>
        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Normal Processing</th>
                    <th>Batch Processing</th>
                    <th>Improvement</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Execution Time</td>
                    <td>{{ $benchmark_comparison['normal_execution_time'] }}s</td>
                    <td>{{ $benchmark_comparison['batch_execution_time'] }}s</td>
                    <td><span class="positive">{{ $benchmark_comparison['speed_improvement_percent'] }}%</span></td>
                </tr>
                <tr>
                    <td>Peak Memory</td>
                    <td>{{ $benchmark_comparison['normal_peak_memory'] }} MB</td>
                    <td>{{ $benchmark_comparison['batch_peak_memory'] }} MB</td>
                    <td><span class="positive">{{ $benchmark_comparison['memory_reduction_percent'] }}%</span></td>
                </tr>
            </tbody>
        </table>
        @endif

        <!-- Orders Sample -->
        @if(!empty($orders))
        <div class="orders-table">
            <h2>Orders Sample (First 50 Orders)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Status</th>
                        <th>Payment Status</th>
                        <th>Total Amount</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td>{{ $order['id'] }}</td>
                        <td>{{ ucfirst($order['status']) }}</td>
                        <td>{{ ucfirst($order['payment_status']) }}</td>
                        <td>${{ number_format($order['total_amount'], 2) }}</td>
                        <td>{{ $order['created_at'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</body>
</html>
