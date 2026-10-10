{{-- รูปสินค้า ถ้าไฟล์ไม่มีจะแสดงกล่องเทาแทน --}}
<div class="img-box">
    @if ($url)
        <img src="{{ $url }}" alt="{{ $alt }}" onerror="this.remove()">
    @endif
    <span>ไม่มีรูป</span>
</div>
