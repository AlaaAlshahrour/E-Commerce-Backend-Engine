<?php

namespace App\Http\Controllers;

use App\Models\DailySalesReport;
use Illuminate\Support\Facades\Storage;

class DailySalesReportController extends Controller
{
    public function show(string $date)
    {
        // البحث عن التقرير
        $report = DailySalesReport::where('date', $date)->first();

        if (! $report) {
            return response()->json(['message' => 'Report not found for the given date.'], 404);
        }

        // إرجاع البيانات مع رابط التحميل وجميع الإحصائيات
        return response()->json([
            'date' => $report->date,
            'total_orders' => $report->total_orders,
            'total_revenue' => $report->total_revenue,
            'export_start_time' => $report->export_start_time,
            'export_end_time' => $report->export_end_time,
            'export_duration_seconds' => $report->export_end_time && $report->export_start_time
                ? $report->export_end_time->diffInSeconds($report->export_start_time)
                : null,
            'pdf_url' => Storage::url($report->pdf_path),
        ]);
    }
}
