<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Sales Report - {{ $date }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .summary { margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Daily Sales Report</h1>
    <p><strong>Date:</strong> {{ $date }}</p>
    <p><strong>Total Orders:</strong> {{ $total_orders }}</p>
    <p><strong>Total Revenue:</strong> ${{ number_format($total_revenue, 2) }}</p>

    <h2>Order Details</h2>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Total Amount</th>
                <th>Created At</th>
                <th>Items</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td>{{ $order['id'] }}</td>
                <td>${{ number_format($order['total_amount'], 2) }}</td>
                <td>{{ $order['created_at'] }}</td>
                <td>
                    <ul>
                        @foreach($order['items'] as $item)
                        <li>{{ $item['product_name'] }} - Qty: {{ $item['quantity'] }}, Price: ${{ number_format($item['unit_price'], 2) }}, Subtotal: ${{ number_format($item['subtotal'], 2) }}</li>
                        @endforeach
                    </ul>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
