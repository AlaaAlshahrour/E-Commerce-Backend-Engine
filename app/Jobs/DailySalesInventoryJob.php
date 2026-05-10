<?php

namespace App\Jobs;

use App\Models\Inventory;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DailySalesInventoryJob implements ShouldQueue
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

        Log::info("Starting daily sales inventory job for date: {$yesterday}");

        $processedCount = 0;

        OrderItem::whereHas('order', function ($query) use ($yesterday) {
            $query->where('status', 'Completed')
                ->whereDate('created_at', $yesterday);
        })->chunk(1000, function ($orderItems) use (&$processedCount) {
            foreach ($orderItems as $item) {
                Inventory::where('product_id', $item->product_id)
                    ->decrement('quantity', $item->quantity);
                $processedCount++;
            }
        });

        Log::info("Daily sales inventory job completed. Processed {$processedCount} order items.");
    }
}
