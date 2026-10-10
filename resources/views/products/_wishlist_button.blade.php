@auth
    @if ($inWishlist)
        <form method="POST" action="{{ route('wishlist.destroy', $productId) }}" style="display:inline">
            @csrf @method('DELETE')
            <button class="{{ $class ?? 'heart' }}" title="เอาออกจาก Wishlist">♥{{ ($withText ?? false) ? ' อยู่ใน Wishlist' : '' }}</button>
        </form>
    @else
        <form method="POST" action="{{ route('wishlist.store', $productId) }}" style="display:inline">
            @csrf
            <button class="{{ $class ?? 'heart' }}" title="เพิ่มลง Wishlist">♡{{ ($withText ?? false) ? ' เพิ่มลง Wishlist' : '' }}</button>
        </form>
    @endif
@endauth
