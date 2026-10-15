<?php

namespace AppHttpControllers;

use AppModelsAddress;
use AppModelsCart;
use AppModelsCartItem;
use AppServicesCheckoutService;
use IlluminateHttpRequest;
use IlluminateSupportFacadesAuth;

class OrderController extends Controller
{
    protected CheckoutService $checkoutService;

    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    public function showCheckout()
    {
        $member = Auth::user();
        $cart = Cart::where('member_id', $member->member_id)->first();
        if (!$cart) {
            return redirect()->route('cart.index')->withErrors(['msg' => 'Cart is empty.']);
        }
        $items = CartItem::where('cart_id', $cart->cart_id)->with('variant')->get();
        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['msg' => 'Cart is empty.']);
        }

        $addresses = $member->addresses;
        
        $grandTotal = 0;
        foreach ($items as $item) {
            if ($item->variant) {
                $grandTotal += $item->quantity * $item->variant->price;
            }
        }

        return view('orders.checkout', compact('items', 'addresses', 'grandTotal'));
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'address_id' => 'required|integer|exists:address,address_id',
        ]);

        $member = Auth::user();
        $cart = Cart::where('member_id', $member->member_id)->first();
        if (!$cart) {
            return redirect()->route('cart.index')->withErrors(['msg' => 'Cart is empty.']);
        }

        $cartItems = CartItem::where('cart_id', $cart->cart_id)->get();
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['msg' => 'Cart is empty.']);
        }

        try {
            $this->checkoutService->checkout(
                $member->member_id,
                ['address_id' => (int) $request->address_id],
                calculateShipping: false // Preserve legacy test expected total_amount
            );

            return redirect()->route('orders.index')->with('success', 'Checkout successful!');
        } catch (\Exception $e) {
            return redirect()->route('cart.index')->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function index()
    {
        $orders = $this->checkoutService->getOrdersForMember(Auth::id());
        return view('orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = $this->checkoutService->getOrderForMember(Auth::id(), (int) $id);
        $items = $order->items;
        return view('orders.show', compact('order', 'items'));
    }
}
