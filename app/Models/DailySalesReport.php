<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySalesReport extends Model
{
    protected $fillable = [
        'date',
        'total_orders',
        'total_revenue',
        'pdf_path',
        'processing_mode',
        'export_start_time',
        'export_end_time',
    ];

    protected $casts = [
        'date' => 'date',
        'export_start_time' => 'datetime',
        'export_end_time' => 'datetime',
        'total_revenue' => 'decimal:2',
    ];
}
