<!DOCTYPE html>
<html>
<head>
    <title>Order Details</title>
</head>
<body>
    <h1>Order #{{ $order->order_id }}</h1>
    
    <p>Status: {{ $order->status }}</p>
    <p>Date: {{ $order->order_date }}</p>
    <p>Shipping Address: {{ $order->ship_name }} - {{ $order->ship_phone }} <br> {{ $order->ship_address }}</p>

    <table border="1">
        <thead>
            <tr>
                <th>Variant ID</th>
                <th>Unit Price</th>
                <th>Quantity</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->variant_id }}</td>
                    <td>{{ $item->unit_price }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->quantity * $item->unit_price }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <h2>Grand Total: {{ $order->total_amount }}</h2>
    
    <a href="{{ route('orders.index') }}">Back to Orders</a>
</body>
</html>
