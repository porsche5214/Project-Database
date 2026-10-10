<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
</head>
<body>
    <h1>Checkout Summary</h1>
    
    @if ($errors->any())
        <div style="color: red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <table border="1">
        <thead>
            <tr>
                <th>Variant ID</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->variant_id }}</td>
                    <td>{{ $item->variant ? $item->variant->price : 'N/A' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->variant ? $item->quantity * $item->variant->price : '0' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <h2>Grand Total: {{ $grandTotal }}</h2>
    
    <form action="{{ route('orders.checkout') }}" method="POST">
        @csrf
        <label>Select Address:</label>
        <select name="address_id" required>
            @foreach($addresses as $address)
                <option value="{{ $address->address_id }}">{{ $address->fullAddress() }}</option>
            @endforeach
        </select>
        <br><br>
        <button type="submit">Place Order</button>
    </form>
</body>
</html>
