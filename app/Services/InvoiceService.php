<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    public function generateInvoicePdf(Order $order): ?string
    {
        $filePath = "public/invoices/invoice-{$order->id}.pdf";

        if (Storage::exists($filePath)) {
            Log::info("Invoice already exists for order {$order->id}");
            return $filePath;
        }

        $order->loadMissing(['user', 'orderItems.product']);

        $invoiceData = [
            'invoice_number'   => 'INV-' . str_pad($order->id, 5, '0', STR_PAD_LEFT),
            'purchase_date'    => $order->created_at->format('Y-m-d'),
            'customer_name'    => $order->user->name,
            'shipping_address' => $order->shipping_address,
            'payment_status'   => $order->payment_status,
            'total_amount'     => $order->total_amount,
            'items' => $order->orderItems->map(function ($item) {
                return [
                    'name'      => $item->product->name,
                    'quantity'  => $item->quantity,
                    'unit_price'=> $item->unit_price,
                    'subtotal'  => $item->quantity * $item->unit_price,
                ];
            })->toArray(),
        ];

        $pdf = app('dompdf.wrapper')->loadView('pdf.invoice', $invoiceData);

        Storage::put($filePath, $pdf->output());

        $order->update(['invoice_path' => $filePath]);

        Log::info("Invoice PDF generated successfully for order {$order->id}");

        return $filePath;
    }
}
