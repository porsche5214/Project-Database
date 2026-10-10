@extends('layouts.shop')

@section('title', 'Wishlist - NaeMak')

@section('content')
<h1>Wishlist ของฉัน</h1>

@if ($products->isEmpty())
    <p class="muted">ยังไม่มีสินค้าใน Wishlist · <a href="{{ route('products.index') }}">ไปดูสินค้า</a></p>
@else
    <div class="grid">
        @foreach ($products as $product)
            <div class="card">
                <a href="{{ route('products.show', $product->product_id) }}">
                    @include('products._image', ['url' => $product->mainImage?->image_url, 'alt' => $product->name])
                </a>
                @include('products._wishlist_button', ['productId' => $product->product_id, 'inWishlist' => true])
                <a href="{{ route('products.show', $product->product_id) }}" class="title">{{ $product->name }}</a>
                <div class="muted">{{ $product->brand?->name }}</div>
                <div style="margin-top:6px">@include('products._stars', ['avg' => $product->ratings_avg_score])</div>
                <div style="font-weight:700; margin-top:6px">฿{{ number_format($product->base_price, 2) }}</div>
                <div class="muted" style="margin-top:4px">เพิ่มเมื่อ {{ \Illuminate\Support\Carbon::parse($product->pivot->added_at)->format('d/m/Y') }}</div>
            </div>
        @endforeach
    </div>
@endif
@endsection
