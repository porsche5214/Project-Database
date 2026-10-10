<!DOCTYPE html>
<html>
<head>
    <title>Cart</title>
</head>
<body>
    <h1>Your Cart</h1>
    
    @if(session('success'))
        <div style="color: green;">{{ session('success') }}</div>
    @endif
    
    @if ($errors->any())
        <div style="color: red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(count($items) > 0)
        <table border="1">
            <thead>
                <tr>
                    <th>Variant ID</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td>{{ $item->variant_id }}</td>
                        <td>{{ $item->variant ? $item->variant->price : 'N/A' }}</td>
                        <td>
                            <form action="{{ route('cart.update', $item->variant_id) }}" method="POST" style="display:inline;">
                                @csrf
                                <input type="number" name="quantity" value="{{ $item->quantity }}" min="1">
                                <button type="submit">Update</button>
                            </form>
                        </td>
                        <td>{{ $item->total }}</td>
                        <td>
                            <form action="{{ route('cart.remove', $item->variant_id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <h2>Grand Total: {{ $grandTotal }}</h2>
        
        <form action="{{ route('cart.clear') }}" method="POST">
            @csrf
            <button type="submit">Clear Cart</button>
        </form>
    @else
        <p>Your cart is empty.</p>
    @endif
</body>
</html>
