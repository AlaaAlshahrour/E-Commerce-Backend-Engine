<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateInvoicePdfJob implements ShouldQueue
{
    use Queueable;
    public $tries = 3;

    public $backoff = 10;
    public function __construct(
        public int $orderId
    ) {}

    public function handle(): void
    {
$jobStartTime = microtime(true);
    try{$order = Order::with([
            'user',
            'orderItems.product',
        ])->find($this->orderId);

        if (! $order) {
    Log::warning(
        "Order {$this->orderId} not found"
    );
            return;
        }

        $invoiceData = [
            'invoice_number' => 'INV-'.str_pad($order->id, 5, '0', STR_PAD_LEFT),

            'purchase_date' => $order->created_at->format('Y-m-d'),

            'customer_name' => $order->user->name,

            'shipping_address' => $order->shipping_address,

            'payment_status' => $order->payment_status,

            'total_amount' => $order->total_amount,

            'items' => $order->orderItems->map(function ($item) {

                return [
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->quantity * $item->unit_price,
                ];
            })->toArray(),
        ];
        $pdf = app('dompdf.wrapper')->loadView(
            'pdf.invoice',
            $invoiceData
        );
        $filePath = "public/invoices/invoice-{$order->id}.pdf";

        if (Storage::exists($filePath)) {
                Log::info(
        "Invoice already exists for order {$order->id}"
    );
        return;
}
        Storage::put(
            $filePath,
            $pdf->output()
        );
Log::info("Invoice PDF generated successfully for order {$order->id}");
$order->update([
    'invoice_path' => $filePath
]);
$jobExecutionTime = microtime(true) - $jobStartTime;

Log::info('Invoice generation time', [
    'order_id' => $order->id,
    'time_seconds' => $jobExecutionTime,
]);
    }catch (Throwable $e) {

        Log::error(
            "Invoice generation failed for order {$this->orderId}",
            [
                'error' => $e->getMessage(),
            ]
        );

        throw $e;
    } } }

