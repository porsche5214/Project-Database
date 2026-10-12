@extends('layouts.shop')

@section('title', 'สินค้า - NaeMak')

@section('content')
<form method="GET" action="{{ route('products.index') }}" style="display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin-bottom:20px">
    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="ค้นหาชื่อสินค้า">

    <select name="category">
        <option value="">ทุกหมวดหมู่</option>
        @foreach ($categories as $cat)
            <option value="{{ $cat->category_id }}" @selected(($filters['category'] ?? null) == $cat->category_id)>{{ $cat->name }}</option>
            @foreach ($cat->children as $child)
                <option value="{{ $child->category_id }}" @selected(($filters['category'] ?? null) == $child->category_id)>&nbsp;&nbsp;— {{ $child->name }}</option>
            @endforeach
        @endforeach
    </select>

    <select name="brand">
        <option value="">ทุกแบรนด์</option>
        @foreach ($brands as $brand)
            <option value="{{ $brand->brand_id }}" @selected(($filters['brand'] ?? null) == $brand->brand_id)>{{ $brand->name }}</option>
        @endforeach
    </select>

    <select name="gender">
        <option value="">ทุกเพศ</option>
        @foreach (['male' => 'ชาย', 'female' => 'หญิง', 'unisex' => 'Unisex'] as $value => $label)
            <option value="{{ $value }}" @selected(($filters['gender'] ?? null) === $value)>{{ $label }}</option>
        @endforeach
    </select>

    <select name="sort">
        @foreach (['newest' => 'ใหม่ล่าสุด', 'price_asc' => 'ราคาต่ำ → สูง', 'price_desc' => 'ราคาสูง → ต่ำ', 'rating' => 'คะแนนสูงสุด'] as $value => $label)
            <option value="{{ $value }}" @selected(($filters['sort'] ?? 'newest') === $value)>{{ $label }}</option>
        @endforeach
    </select>

    <label style="display:flex; align-items:center; gap:4px; font-size:14px; margin-left:4px">
        <input type="checkbox" name="in_stock" value="1" @checked(!empty($filters['in_stock']))> เฉพาะที่มีของ
    </label>

    <button class="btn">กรอง</button>
    <a href="{{ route('products.index') }}" class="btn outline">ล้าง</a>
</form>

<p class="muted">พบ {{ $products->total() }} รายการ</p>

<div class="grid">
    @forelse ($products as $product)
        <div class="card">
            <a href="{{ route('products.show', $product->product_id) }}">
                @include('products._image', ['url' => $product->mainImage?->image_url, 'alt' => $product->name])
            </a>
            @include('products._wishlist_button', ['productId' => $product->product_id, 'inWishlist' => in_array($product->product_id, $wishlistIds)])
            <a href="{{ route('products.show', $product->product_id) }}" class="title">{{ $product->name }}</a>
            <div class="muted">{{ $product->brand?->name }} · {{ $product->category->name }}</div>
            <div style="margin-top:6px">
                @include('products._stars', ['avg' => $product->ratings_avg_score])
                <span class="muted">({{ $product->ratings_count }})</span>
            </div>
            <div style="font-weight:700; margin-top:6px">฿{{ number_format($product->base_price, 2) }}</div>
        </div>
    @empty
        <p>ไม่พบสินค้าที่ตรงกับตัวกรอง</p>
    @endforelse
</div>

<div style="margin-top:20px">{{ $products->links() }}</div>
@endsection
