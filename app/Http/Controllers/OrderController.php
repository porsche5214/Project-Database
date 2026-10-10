<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;
use App\Models\ProductVariant;

class OrderController extends Controller
{
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
        $address = Address::where('address_id', $request->address_id)
            ->where('member_id', $member->member_id)
            ->firstOrFail();

        $cart = Cart::where('member_id', $member->member_id)->first();
        if (!$cart) {
            return redirect()->route('cart.index')->withErrors(['msg' => 'Cart is empty.']);
        }

        $cartItems = CartItem::where('cart_id', $cart->cart_id)->get();
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['msg' => 'Cart is empty.']);
        }

        try {
            DB::transaction(function () use ($member, $address, $cart, $cartItems) {
                $totalAmount = 0;
                $orderItemsData = [];

                foreach ($cartItems as $item) {
                    $variant = ProductVariant::where('variant_id', $item->variant_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$variant || $variant->stock < $item->quantity) {
                        throw new \Exception('Not enough stock for variant ID: ' . $item->variant_id);
                    }

                    ProductVariant::where('variant_id', $item->variant_id)
                        ->update(['stock' => $variant->stock - $item->quantity]);

                    $totalAmount += $item->quantity * $variant->price;

                    $orderItemsData[] = [
                        'variant_id' => $variant->variant_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $variant->price,
                    ];
                }

                $order = Order::create([
                    'member_id' => $member->member_id,
                    'contact_email' => $member->email,
                    'ship_name' => $address->recipient_name,
                    'ship_phone' => $address->phone,
                    'ship_address' => $address->fullAddress(),
                    'total_amount' => $totalAmount,
                    'status' => 'pending'
                ]);

                foreach ($orderItemsData as $data) {
                    $data['order_id'] = $order->order_id;
                    OrderItem::insert($data);
                }

                CartItem::where('cart_id', $cart->cart_id)->delete();
            });
            
            return redirect()->route('orders.index')->with('success', 'Checkout successful!');
        } catch (\Exception $e) {
            return redirect()->route('cart.index')->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function index()
    {
        $orders = Order::where('member_id', Auth::id())->orderBy('order_id', 'desc')->get();
        return view('orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::where('order_id', $id)->where('member_id', Auth::id())->firstOrFail();
        $items = OrderItem::where('order_id', $order->order_id)->with('variant')->get();
        return view('orders.show', compact('order', 'items'));
    }
}
