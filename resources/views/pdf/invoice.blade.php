<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice</title>

    <style>
        body {
            font-family: sans-serif;
            margin: 30px;
        }

        h1 {
            text-align: center;
        }

        .section {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }
    </style>
</head>

<body>

    <h1>Invoice</h1>

    <div class="section">
        <strong>Invoice Number:</strong>{{ $invoice_number }}
        <br>

        <strong>Purchase Date:</strong>{{ $purchase_date }}
        <br>

        <strong>Customer Name:</strong>{{ $customer_name }}
        <br>

        <strong>Shipping Address:</strong> {{ $shipping_address }}
        <br>

        <strong>Payment Status:</strong> {{ $payment_status  }}
    </div>

    <table>
        <thead>
        <tr>
            <th>Product</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>Subtotal</th>
        </tr>
        </thead>

        <tbody>
        @foreach($items as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td>{{ $item['quantity'] }}</td>
                <td>${{ $item['unit_price'] }}</td>
                <td>${{ $item['subtotal'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <br>

    <strong>Total Amount:</strong>${{ $total_amount }}

</body>
</html>
