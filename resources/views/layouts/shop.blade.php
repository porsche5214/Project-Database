<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'NaeMak')</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, sans-serif; color: #222; background: #fafafa; }
        a { color: inherit; }
        header { background: #111; color: #fff; padding: 12px 24px; display: flex; gap: 20px; align-items: center; }
        header .logo { font-weight: 700; font-size: 20px; text-decoration: none; margin-right: auto; }
        header a { text-decoration: none; }
        main { max-width: 1100px; margin: 0 auto; padding: 24px; }
        .flash { background: #e7f6ec; border: 1px solid #9bd3ae; padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; }
        .errors { background: #fdecec; border: 1px solid #f1a5a5; padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; }
        .btn { display: inline-block; padding: 8px 16px; border: 1px solid #111; background: #111; color: #fff; border-radius: 6px; cursor: pointer; text-decoration: none; font-size: 14px; }
        .btn.outline { background: #fff; color: #111; }
        .btn:disabled { opacity: .4; cursor: not-allowed; }
        .muted { color: #777; font-size: 14px; }
        .stars { color: #f5a623; letter-spacing: 1px; }
        .img-box { position: relative; aspect-ratio: 1; background: #e9e9e9; display: flex; align-items: center; justify-content: center; color: #999; overflow: hidden; border-radius: 8px; }
        .img-box img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        a:has(> .img-box) { text-decoration: none; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
        .card { background: #fff; border: 1px solid #eee; border-radius: 10px; padding: 12px; position: relative; }
        .card a.title { text-decoration: none; font-weight: 600; display: block; margin: 8px 0 4px; }
        .heart { position: absolute; top: 18px; right: 18px; background: #fff; border: none; border-radius: 50%; width: 34px; height: 34px; cursor: pointer; font-size: 18px; }
        select, input, textarea { padding: 7px 10px; border: 1px solid #ccc; border-radius: 6px; font: inherit; }
    </style>
    @stack('head')
</head>
<body>
<header>
    <a href="{{ route('products.index') }}" class="logo">NaeMak</a>
    <a href="{{ route('products.index') }}">สินค้า</a>
    @auth
        <a href="{{ route('wishlist.index') }}">♥ Wishlist</a>
        @if (Route::has('cart.index'))<a href="{{ route('cart.index') }}">ตะกร้า</a>@endif
        <span>{{ auth()->user()->username }}</span>
    @else
        @if (Route::has('cart.index'))<a href="{{ route('cart.index') }}">ตะกร้า</a>@endif
        @if (Route::has('login'))<a href="{{ route('login') }}">เข้าสู่ระบบ</a>@endif
    @endauth
</header>
<main>
    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())
        <div class="errors">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif
    @yield('content')
</main>
@stack('scripts')
</body>
</html>
