<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index()
    {
        $cart = $this->cartService->getCartForMember(Auth::id());
        $items = CartItem::where('cart_id', $cart->cart_id)->with('variant')->get();

        $grandTotal = 0;
        foreach ($items as $item) {
            if ($item->variant) {
                $item->total = $item->quantity * $item->variant->price;
                $grandTotal += $item->total;
            } else {
                $item->total = 0;
            }
        }

        return view('cart.index', compact('items', 'grandTotal'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|integer|exists:product_variant,variant_id',
            'quantity' => 'required|integer|min:1',
        ]);

        $this->cartService->addItem(Auth::id(), (int) $request->variant_id, (int) $request->quantity);

        return back()->with('success', 'Added to cart');
    }

    public function update(Request $request, $variant_id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $this->cartService->updateQuantity(Auth::id(), (int) $variant_id, (int) $request->quantity);

        return back()->with('success', 'Cart updated');
    }

    public function remove($variant_id)
    {
        $this->cartService->removeItem(Auth::id(), (int) $variant_id);

        return back()->with('success', 'Item removed');
    }

    public function clear()
    {
        $this->cartService->clearCart(Auth::id());

        return back()->with('success', 'Cart cleared');
    }
}
