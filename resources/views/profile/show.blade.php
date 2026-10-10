<!DOCTYPE html>
<html>
<head>
    <title>Member Profile</title>
</head>
<body>
    <h1>Profile</h1>
    <p>Username: {{ $member->username }}</p>
    <p>Email: {{ $member->email }}</p>
    <p>Role: {{ $member->role }}</p>

    <h2>Addresses</h2>
    @if($addresses->count() > 0)
        <ul>
            @foreach($addresses as $address)
                <li>{{ $address->fullAddress() }} ({{ $address->is_default ? 'Default' : 'Other' }})</li>
            @endforeach
        </ul>
    @else
        <p>No addresses found.</p>
    @endif

    <form action="{{ url('/logout') }}" method="POST">
        @csrf
        <button type="submit">Logout</button>
    </form>
</body>
</html>
