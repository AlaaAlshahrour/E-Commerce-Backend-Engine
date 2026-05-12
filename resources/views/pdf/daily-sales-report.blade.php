<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Processing Performance Analysis Report - {{ $date }}</title>
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
        .executive-summary { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .executive-summary table { margin-top: 10px; }
        .executive-summary th { background-color: #e9ecef; }
        .insights { background-color: #e8f5e8; padding: 10px; border-left: 4px solid #28a745; margin: 10px 0; }
        .academic-section { background-color: #f0f8ff; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .academic-section ul { margin-left: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <h1>Batch Processing Performance Analysis Report</h1>

        {{-- ===== Sales Statistics Table ===== --}}
        <div class="executive-summary">
            <h2>Sales Statistics</h2>
            <table>            <thead>
                <tr style="background:#f0f0f0;">
                    <th>Total Cost</th>
                    <th>Average Order</th>
                    <th>Completed</th>
                    <th>Cancelled</th>
                    <th>Processing</th>
                    <th>Pending</th>
                </tr>
                </thead>
                <tbody>
                <tr style="text-align:center;">
                    <td>{{ number_format($order_stats['total_cost'], 2) }}</td>
                    <td>{{ number_format($order_stats['average_order'], 2) }}</td>
                    <td>{{ $order_stats['completed_orders'] }}</td>
                    <td>{{ $order_stats['canceled_orders'] }}</td>
                    <td>{{ $order_stats['processing_orders'] }}</td>
                    <td>{{ $order_stats['pending_orders'] }}</td>
                </tr>
                </tbody>
            </table>
        </div>
        <!-- Executive Summary -->
        <div class="executive-summary">
            <h2>Executive Summary</h2>
            <table>
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Normal Processing</th>
                        <th>Batch Processing</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Orders Processed</td>
                        <td>{{ $normal_stats['orders_processed'] ?? 'N/A' }}</td>
                        <td>{{ $batch_stats['orders_processed'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Execution Time</td>
                        <td>{{ $normal_stats['execution_time'] ?? 'N/A' }}s</td>
                        <td>{{ $batch_stats['execution_time'] ?? 'N/A' }}s</td>
                    </tr>
                    <tr>
                        <td>Peak Memory Usage</td>
                        <td>{{ $normal_stats['peak_memory_real'] ?? 'N/A' }} MB</td>
                        <td>{{ $batch_stats['peak_memory_real'] ?? 'N/A' }} MB</td>
                    </tr>
                    <tr>
                        <td>Total Memory Increase</td>
                        <td>{{ $normal_stats['memory_delta'] ?? 'N/A' }} MB</td>
                        <td>{{ $batch_stats['memory_delta'] ?? 'N/A' }} MB</td>
                    </tr>
                    <tr>
                        <td>Processing Status</td>
                        <td>{{ $normal_stats['status'] ?? 'N/A' }}</td>
                        <td>{{ $batch_stats['status'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Batch Count</td>
                        <td>N/A</td>
                        <td>{{ $batch_stats['batch_count'] ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Batch Size</td>
                        <td>N/A</td>
                        <td>{{ $batch_stats['batch_size'] ?? 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Memory Consumption Analysis -->
        <h2>Memory Consumption Analysis</h2>
        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Normal Processing</th>
                    <th>Batch Processing</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Start Memory (Real)</td>
                    <td>{{ $normal_stats['start_memory_real'] ?? 'N/A' }} MB</td>
                    <td>{{ $batch_stats['start_memory_real'] ?? 'N/A' }} MB</td>
                </tr>
                <tr>
                    <td>End Memory (Real)</td>
                    <td>{{ $normal_stats['end_memory_real'] ?? 'N/A' }} MB</td>
                    <td>{{ $batch_stats['end_memory_real'] ?? 'N/A' }} MB</td>
                </tr>
                <tr>
                    <td>Peak Memory (Real)</td>
                    <td>{{ $normal_stats['peak_memory_real'] ?? 'N/A' }} MB</td>
                    <td>{{ $batch_stats['peak_memory_real'] ?? 'N/A' }} MB</td>
                </tr>
                <tr>
                    <td>Memory Increase</td>
                    <td>{{ $normal_stats['memory_delta'] ?? 'N/A' }} MB</td>
                    <td>{{ $batch_stats['memory_delta'] ?? 'N/A' }} MB</td>
                </tr>
                <tr>
                    <td>Allocated Memory</td>
                    <td>{{ $normal_stats['peak_memory_allocated'] ?? 'N/A' }} MB</td>
                    <td>{{ $batch_stats['peak_memory_allocated'] ?? 'N/A' }} MB</td>
                </tr>
                <tr>
                    <td>Peak Allocated Memory</td>
                    <td>{{ $normal_stats['peak_memory_allocated'] ?? 'N/A' }} MB</td>
                    <td>{{ $batch_stats['peak_memory_allocated'] ?? 'N/A' }} MB</td>
                </tr>
                <tr>
                    <td>Number of Batches</td>
                    <td>N/A</td>
                    <td>{{ $batch_stats['batch_count'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Average Batch Memory</td>
                    <td>N/A</td>
                    <td>{{ $batch_stats['average_batch_memory'] ?? 'N/A' }} MB</td>
                </tr>
                <tr>
                    <td>Largest Batch Memory</td>
                    <td>N/A</td>
                    <td>{{ $batch_stats['largest_batch_memory'] ?? 'N/A' }} MB</td>
                </tr>
                <tr>
                    <td>Smallest Batch Memory</td>
                    <td>N/A</td>
                    <td>{{ $batch_stats['smallest_batch_memory'] ?? 'N/A' }} MB</td>
                </tr>
            </tbody>
        </table>

        <!-- Performance Insights -->
        <div class="insights">
            <h2>Performance Insights</h2>
            <p>
                @if(isset($comparison['memory_reduction_percent']) && $comparison['memory_reduction_percent'] > 0)
                    Normal Processing loaded all orders into memory at once, causing very high memory usage of {{ $normal_stats['peak_memory_real'] ?? 'N/A' }} MB.
                    Batch Processing kept memory stable by processing orders in chunks, reducing peak memory to {{ $batch_stats['peak_memory_real'] ?? 'N/A' }} MB.
                    This represents a {{ $comparison['memory_reduction_percent'] }}% reduction in memory usage.
                @else
                    Batch Processing maintained efficient memory usage through chunked processing.
                @endif
            </p>
            <p>
                @if(isset($comparison['speed_improvement_percent']))
                    @if($comparison['speed_improvement_percent'] > 0)
                        Batch Processing was {{ $comparison['speed_improvement_percent'] }}% faster than Normal Processing.
                    @elseif($comparison['speed_improvement_percent'] < 0)
                        Normal Processing was faster in this case, but Batch Processing provides better scalability.
                    @else
                        Execution times were comparable between both methods.
                    @endif
                @endif
            </p>
            <p>
                System scalability improved significantly using chunked processing, allowing handling of larger datasets without memory exhaustion.
            </p>
        </div>

        <!-- Performance Percentage -->
        @if(isset($comparison['memory_reduction_percent']))
        <h2>Performance Analysis</h2>
        <p>Memory Reduction %: {{ $comparison['memory_reduction_percent'] }}%</p>
        <p>Batch Processing was {{ $comparison['speed_improvement_percent'] }}% faster than Normal Processing.</p>
        @endif

    </div>
</body>
</html>
