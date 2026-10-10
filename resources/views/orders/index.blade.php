<!DOCTYPE html>
<html>
<head>
    <title>My Orders</title>
</head>
<body>
    <h1>My Orders</h1>
    
    @if(session('success'))
        <div style="color: green;">{{ session('success') }}</div>
    @endif

    @if(count($orders) > 0)
        <ul>
            @foreach($orders as $order)
                <li>
                    <a href="{{ route('orders.show', $order->order_id) }}">Order #{{ $order->order_id }}</a> - 
                    Total: {{ $order->total_amount }} - Status: {{ $order->status }} - Date: {{ $order->order_date }}
                </li>
            @endforeach
        </ul>
    @else
        <p>You have no orders.</p>
    @endif
</body>
</html>
