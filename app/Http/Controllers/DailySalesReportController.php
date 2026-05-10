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

        // إرجاع البيانات مع رابط التحميل
        return response()->json([
            'date' => $report->date,
            'total_orders' => $report->total_orders,
            'total_revenue' => $report->total_revenue,
            'pdf_url' => Storage::url($report->pdf_path),
        ]);
    }
}
