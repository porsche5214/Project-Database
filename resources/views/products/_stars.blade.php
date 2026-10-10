{{-- ใช้: @include('products._stars', ['avg' => 4.3]) --}}
@php($full = (int) round($avg ?? 0))
<span class="stars" title="{{ number_format($avg ?? 0, 1) }}">{{ str_repeat('★', $full) }}<span style="color:#ccc">{{ str_repeat('★', 5 - $full) }}</span></span>
