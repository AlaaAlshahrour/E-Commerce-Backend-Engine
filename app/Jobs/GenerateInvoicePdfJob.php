<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GenerateInvoicePdfJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $orderId
    ) {}

    public function handle(): void
    {
        $order = Order::with([
            'user',
            'orderItems.product',
        ])->find($this->orderId);

        if (! $order) {
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

        Storage::put(
            "public/invoices/invoice-{$order->id}.pdf",
            $pdf->output()
        );
    }
}
