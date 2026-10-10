<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;

class CartController extends Controller
{
    private function getCart()
    {
        return Cart::firstOrCreate(['member_id' => Auth::id()]);
    }

    public function index()
    {
        $cart = $this->getCart();
        // Assume variant model has price. We load variants to calculate totals.
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

        $variant = ProductVariant::findOrFail($request->variant_id);
        
        $cart = $this->getCart();
        $cartItem = CartItem::where('cart_id', $cart->cart_id)
            ->where('variant_id', $request->variant_id)
            ->first();

        $requestedQuantity = $request->quantity;
        if ($cartItem) {
            $requestedQuantity += $cartItem->quantity;
        }

        // Basic stock check
        if ($variant->stock < $requestedQuantity) {
            return back()->withErrors(['quantity' => 'Not enough stock.']);
        }

        if ($cartItem) {
            CartItem::where('cart_id', $cart->cart_id)
                ->where('variant_id', $request->variant_id)
                ->update(['quantity' => $requestedQuantity]);
        } else {
            CartItem::insert([
                'cart_id' => $cart->cart_id,
                'variant_id' => $request->variant_id,
                'quantity' => $request->quantity,
            ]);
        }

        return back()->with('success', 'Added to cart');
    }

    public function update(Request $request, $variant_id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $this->getCart();
        $cartItem = CartItem::where('cart_id', $cart->cart_id)
            ->where('variant_id', $variant_id)
            ->first();

        if (!$cartItem) {
            return back()->withErrors(['msg' => 'Item not in cart.']);
        }

        $variant = ProductVariant::findOrFail($variant_id);
        if ($variant->stock < $request->quantity) {
            return back()->withErrors(['quantity' => 'Not enough stock.']);
        }

        CartItem::where('cart_id', $cart->cart_id)
            ->where('variant_id', $variant_id)
            ->update(['quantity' => $request->quantity]);

        return back()->with('success', 'Cart updated');
    }

    public function remove($variant_id)
    {
        $cart = $this->getCart();
        CartItem::where('cart_id', $cart->cart_id)
            ->where('variant_id', $variant_id)
            ->delete();

        return back()->with('success', 'Item removed');
    }

    public function clear()
    {
        $cart = $this->getCart();
        CartItem::where('cart_id', $cart->cart_id)->delete();

        return back()->with('success', 'Cart cleared');
    }
}
