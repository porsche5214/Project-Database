@extends('layouts.shop')

@section('title', 'ตะกร้าสินค้า - NaeMak')

@section('content')
<h1>ตะกร้าสินค้าของคุณ</h1>

@if(count($items) > 0)
    <div style="background:#fff; border:1px solid #eee; border-radius:10px; padding:20px; margin-bottom:20px">
        <table style="width:100%; border-collapse:collapse; text-align:left">
            <thead>
                <tr style="border-bottom:2px solid #eee; padding-bottom:10px">
                    <th style="padding:10px 8px">สินค้า</th>
                    <th style="padding:10px 8px">ตัวเลือก (สี / ไซซ์)</th>
                    <th style="padding:10px 8px">ราคาต่อชิ้น</th>
                    <th style="padding:10px 8px; width:140px">จำนวน</th>
                    <th style="padding:10px 8px">ราคารวม</th>
                    <th style="padding:10px 8px; text-align:right">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr style="border-bottom:1px solid #eee">
                        <td style="padding:12px 8px">
                            <strong>{{ $item->variant?->product?->name ?? 'สินค้า (รหัสตัวเลือก: '.$item->variant_id.')' }}</strong>
                        </td>
                        <td style="padding:12px 8px">
                            @if($item->variant)
                                <span>สี: {{ $item->variant->color }} | ไซซ์: {{ $item->variant->size }}</span>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>
                        <td style="padding:12px 8px">
                            ฿{{ $item->variant ? number_format($item->variant->price, 2) : 'N/A' }}
                        </td>
                        <td style="padding:12px 8px">
                            <form action="{{ route('cart.update', $item->variant_id) }}" method="POST" style="display:flex; gap:6px; align-items:center">
                                @csrf
                                <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $item->variant?->stock }}" style="width:65px">
                                <button type="submit" class="btn outline" style="padding:4px 8px; font-size:12px">อัปเดต</button>
                            </form>
                        </td>
                        <td style="padding:12px 8px; font-weight:600">
                            ฿{{ number_format($item->total, 2) }}
                        </td>
                        <td style="padding:12px 8px; text-align:right">
                            <form action="{{ route('cart.remove', $item->variant_id) }}" method="POST" style="display:inline" onsubmit="return confirm('นำสินค้านี้ออกจากตะกร้า?')">
                                @csrf
                                <button type="submit" class="btn outline" style="padding:4px 10px; font-size:12px; color:#d9534f; border-color:#d9534f">ลบ</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:24px; padding-top:16px; border-top:2px solid #eee">
            <form action="{{ route('cart.clear') }}" method="POST" onsubmit="return confirm('ต้องการล้างตะกร้าสินค้าทั้งหมด?')">
                @csrf
                <button type="submit" class="btn outline" style="color:#777">ล้างตะกร้าทั้งหมด</button>
            </form>

            <div style="text-align:right">
                <div style="font-size:16px; margin-bottom:8px">ยอดรวมทั้งสิ้น: <strong style="font-size:24px; color:#111">฿{{ number_format($grandTotal, 2) }}</strong></div>
                <div style="display:flex; gap:10px; justify-content:flex-end">
                    <a href="{{ route('products.index') }}" class="btn outline">เลือกซื้อสินค้าเพิ่ม</a>
                    <a href="{{ route('orders.checkout_show') }}" class="btn" style="font-size:16px; padding:10px 24px">ดำเนินการสั่งซื้อ (Checkout)</a>
                </div>
            </div>
        </div>
    </div>
@else
    <div style="background:#fff; border:1px solid #eee; border-radius:10px; padding:40px; text-align:center">
        <p class="muted" style="font-size:16px; margin-bottom:16px">ไม่มีสินค้าในตะกร้าของคุณ</p>
        <a href="{{ route('products.index') }}" class="btn">เลือกซื้อสินค้า</a>
    </div>
@endif
@endsection
