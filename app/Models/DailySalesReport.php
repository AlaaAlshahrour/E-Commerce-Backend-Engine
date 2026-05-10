<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySalesReport extends Model
{
    protected $fillable = ['date', 'total_orders', 'total_revenue', 'pdf_path'];

    protected $casts = [
        'date' => 'date',
        'total_revenue' => 'decimal:2',
    ];
}
