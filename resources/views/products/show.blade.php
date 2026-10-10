@extends('layouts.shop')

@section('title', $product->name.' - NaeMak')

@section('content')
<p class="muted">
    <a href="{{ route('products.index') }}">สินค้า</a>
    @if ($product->category->parent) / <a href="{{ route('products.index', ['category' => $product->category->parent->category_id]) }}">{{ $product->category->parent->name }}</a>@endif
    / <a href="{{ route('products.index', ['category' => $product->category_id]) }}">{{ $product->category->name }}</a>
</p>

<div style="display:grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap:32px">
    <div>
        @php($main = $product->images->firstWhere('is_main', true) ?? $product->images->first())
        <div id="main-image">@include('products._image', ['url' => $main?->image_url, 'alt' => $product->name])</div>
        @if ($product->images->count() > 1)
            <div style="display:flex; gap:8px; margin-top:8px">
                @foreach ($product->images as $img)
                    <div style="width:70px; cursor:pointer" onclick="document.querySelector('#main-image img')?.setAttribute('src', '{{ $img->image_url }}')">
                        @include('products._image', ['url' => $img->image_url, 'alt' => $product->name])
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div>
        <div class="muted">{{ $product->brand?->name }}</div>
        <h1 style="margin:4px 0 8px">{{ $product->name }}</h1>
        <div>
            @include('products._stars', ['avg' => $product->ratings_avg_score])
            <strong>{{ number_format($product->ratings_avg_score ?? 0, 1) }}</strong>
            <span class="muted">/ 5 ({{ $product->ratings_count }} คะแนน)</span>
        </div>
        <div id="price" style="font-size:26px; font-weight:700; margin:12px 0">฿{{ number_format($product->base_price, 2) }}</div>
        <p>{{ $product->description }}</p>

        @if ($product->variants->isEmpty())
            <p class="muted">สินค้านี้ยังไม่มีสี/ไซซ์ให้เลือก</p>
        @else
            <form method="POST" action="{{ Route::has('cart.add') ? route('cart.add') : '#' }}" id="variant-form">
                @csrf
                <input type="hidden" name="variant_id" id="variant_id">

                <div style="margin-bottom:12px">
                    <div style="margin-bottom:6px">สี: <strong id="color-label">-</strong></div>
                    <div id="colors" style="display:flex; gap:8px; flex-wrap:wrap"></div>
                </div>
                <div style="margin-bottom:12px">
                    <div style="margin-bottom:6px">ไซซ์: <strong id="size-label">-</strong></div>
                    <div id="sizes" style="display:flex; gap:8px; flex-wrap:wrap"></div>
                </div>
                <div id="stock" class="muted" style="margin-bottom:12px">เลือกสีและไซซ์</div>

                <div style="display:flex; gap:8px; align-items:center">
                    <input type="number" name="quantity" id="quantity" value="1" min="1" style="width:80px">
                    @if (Route::has('cart.add'))
                        <button class="btn" id="add-btn" disabled>ใส่ตะกร้า</button>
                    @else
                        <button class="btn" disabled title="รอเชื่อมกับระบบตะกร้า">ใส่ตะกร้า</button>
                    @endif
                </div>
            </form>
        @endif

        <div style="margin-top:12px">
            @include('products._wishlist_button', ['productId' => $product->product_id, 'inWishlist' => $inWishlist, 'class' => 'btn outline', 'withText' => true])
        </div>
    </div>
</div>

<hr style="margin:32px 0">

<div style="display:grid; grid-template-columns: 260px minmax(0,1fr); gap:32px">
    <div>
        <h2 style="margin-top:0">คะแนน</h2>
        <div style="font-size:40px; font-weight:700">{{ number_format($product->ratings_avg_score ?? 0, 1) }}</div>
        @include('products._stars', ['avg' => $product->ratings_avg_score])
        <div class="muted">จาก {{ $product->ratings_count }} คน</div>
        @foreach ([5, 4, 3, 2, 1] as $s)
            @php($n = $scoreCounts[$s] ?? 0)
            <div style="display:flex; align-items:center; gap:6px; font-size:13px; margin-top:4px">
                <span style="width:24px">{{ $s }}★</span>
                <div style="flex:1; height:8px; background:#eee; border-radius:4px">
                    <div style="height:8px; background:#f5a623; border-radius:4px; width:{{ $product->ratings_count ? round($n / $product->ratings_count * 100) : 0 }}%"></div>
                </div>
                <span style="width:20px; text-align:right">{{ $n }}</span>
            </div>
        @endforeach

        @auth
            <form method="POST" action="{{ route('ratings.store', $product->product_id) }}" style="margin-top:16px">
                @csrf
                <div style="margin-bottom:6px">{{ $myScore ? "คะแนนของคุณ: $myScore ดาว (ให้ใหม่ได้)" : 'ให้คะแนนสินค้านี้' }}</div>
                <select name="score" required>
                    @foreach ([5, 4, 3, 2, 1] as $s)
                        <option value="{{ $s }}" @selected($myScore == $s)>{{ str_repeat('★', $s) }} ({{ $s }})</option>
                    @endforeach
                </select>
                <button class="btn">ให้คะแนน</button>
            </form>
        @endauth
    </div>

    <div>
        <h2 style="margin-top:0">รีวิว ({{ $product->reviews->count() }})</h2>
        @auth
            <form method="POST" action="{{ route('reviews.store', $product->product_id) }}" style="margin-bottom:20px">
                @csrf
                <textarea name="comment" rows="3" style="width:100%" placeholder="เขียนรีวิวสินค้านี้" required maxlength="1000">{{ old('comment') }}</textarea>
                <button class="btn" style="margin-top:6px">ส่งรีวิว</button>
            </form>
        @else
            <p class="muted">
                @if (Route::has('login'))<a href="{{ route('login') }}">เข้าสู่ระบบ</a>@else เข้าสู่ระบบ @endif
                เพื่อให้คะแนน เขียนรีวิว และเพิ่ม Wishlist
            </p>
        @endauth

        @forelse ($product->reviews as $review)
            <div style="border-bottom:1px solid #eee; padding:12px 0">
                <strong>{{ $review->member?->username ?? 'ผู้ใช้' }}</strong>
                <span class="muted">· {{ \Illuminate\Support\Carbon::parse($review->created_at)->format('d/m/Y H:i') }}</span>
                <p style="margin:6px 0">{{ $review->comment }}</p>
                @auth
                    @if ((int) $review->member_id === (int) auth()->id() || auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('reviews.destroy', $review->review_id) }}" onsubmit="return confirm('ลบรีวิวนี้?')">
                            @csrf @method('DELETE')
                            <button class="btn outline" style="padding:2px 10px; font-size:12px">ลบ</button>
                        </form>
                    @endif
                @endauth
            </div>
        @empty
            <p class="muted">ยังไม่มีรีวิว</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const variants = @json($variantData);
    if (!variants.length) return;

    const colors = [...new Set(variants.map(v => v.color))];
    const el = id => document.getElementById(id);
    let color = null, variant = null;

    function chip(text, active, disabled, onClick) {
        const b = document.createElement('button');
        b.type = 'button';
        b.textContent = text;
        b.className = 'btn' + (active ? '' : ' outline');
        b.disabled = disabled;
        b.onclick = onClick;
        return b;
    }

    function render() {
        el('colors').replaceChildren(...colors.map(c =>
            chip(c, c === color, false, () => { color = c; variant = null; render(); })
        ));
        el('color-label').textContent = color ?? '-';

        const sizes = color ? variants.filter(v => v.color === color) : [];
        el('sizes').replaceChildren(...sizes.map(v =>
            chip(v.size + (v.stock > 0 ? '' : ' (หมด)'), variant?.id === v.id, v.stock <= 0, () => { variant = v; render(); })
        ));
        el('size-label').textContent = variant?.size ?? '-';

        el('variant_id').value = variant?.id ?? '';
        el('quantity').max = variant?.stock ?? '';
        if (variant) {
            el('price').textContent = '฿' + variant.price.toLocaleString('th-TH', { minimumFractionDigits: 2 });
            el('stock').textContent = 'เหลือ ' + variant.stock + ' ชิ้น';
        } else {
            el('stock').textContent = color ? 'เลือกไซซ์' : 'เลือกสีและไซซ์';
        }
        const add = el('add-btn');
        if (add) add.disabled = !variant || variant.stock <= 0;
    }

    // เลือกสีแรกที่มีของไว้ก่อน
    color = (variants.find(v => v.stock > 0) ?? variants[0]).color;
    render();
})();
</script>
@endpush
