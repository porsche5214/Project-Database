<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /**
     * Process checkout for a member.
     *
     * @param int $memberId
     * @param array $shippingData
     * @param bool $calculateShipping
     * @return Order
     * @throws \Exception
     */
    public function checkout(int $memberId, array $shippingData = [], bool $calculateShipping = true): Order
    {
        $member = Member::findOrFail($memberId);

        $cart = Cart::where('member_id', $memberId)->first();
        if (!$cart) {
            throw ValidationException::withMessages(['cart' => 'Cart is empty.']);
        }

        $cartItems = CartItem::where('cart_id', $cart->cart_id)->get();
        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Cart is empty.']);
        }

        return DB::transaction(function () use ($member, $cart, $cartItems, $shippingData, $calculateShipping) {
            $subtotal = 0;
            $orderItemsData = [];

            foreach ($cartItems as $item) {
                // Lock variant row for update to prevent race conditions
                $variant = ProductVariant::where('variant_id', $item->variant_id)
                    ->lockForUpdate()
                    ->first();

                if (!$variant) {
                    throw new \Exception('An item in your bag is no longer available. Please remove it and try again.');
                }
                if ($variant->stock < $item->quantity) {
                    $name = $variant->product?->name ?? 'An item';
                    $left = $variant->stock > 0 ? "only {$variant->stock} left" : 'it is sold out';
                    throw new \Exception("Not enough stock for {$name} ({$variant->color} / {$variant->size}): {$left}. Please update your bag.");
                }

                // Decrement stock
                ProductVariant::where('variant_id', $item->variant_id)
                    ->update(['stock' => $variant->stock - $item->quantity]);

                $lineTotal = $item->quantity * (float) $variant->price;
                $subtotal += $lineTotal;

                $orderItemsData[] = [
                    'variant_id' => $variant->variant_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $variant->price,
                ];
            }

            // Server-side shipping fee calculation (?150 if < ?2500, else 0)
            $shippingFee = ($calculateShipping && $subtotal > 0 && $subtotal < 2500) ? 150.0 : 0.0;
            $totalAmount = $subtotal + $shippingFee;

            // Resolve shipping snapshot
            $shipName = '';
            $shipPhone = '';
            $shipAddress = '';
            $contactEmail = $shippingData['contact_email'] ?? ($shippingData['email'] ?? $member->email);

            if (!empty($shippingData['address_id'])) {
                $address = Address::where('address_id', $shippingData['address_id'])
                    ->where('member_id', $member->member_id)
                    ->firstOrFail();

                $shipName = $address->recipient_name;
                $shipPhone = $address->phone;
                $shipAddress = method_exists($address, 'fullAddress')
                    ? $address->fullAddress()
                    : trim("{$address->address_line} {$address->subdistrict} {$address->district} {$address->province} {$address->postal_code}");
            } else {
                $firstName = $shippingData['first_name'] ?? ($shippingData['firstName'] ?? '');
                $lastName = $shippingData['last_name'] ?? ($shippingData['lastName'] ?? '');
                $shipName = trim($shippingData['ship_name'] ?? "$firstName $lastName");
                if (empty($shipName)) {
                    $shipName = trim("{$member->first_name} {$member->last_name}") ?: $member->username;
                }

                $shipPhone = $shippingData['ship_phone'] ?? ($shippingData['phone'] ?? $member->phone ?? '');

                $addrLine = $shippingData['address'] ?? ($shippingData['address_line'] ?? '');
                $city = $shippingData['city'] ?? ($shippingData['district'] ?? '');
                $province = $shippingData['province'] ?? '';
                $postalCode = $shippingData['postalCode'] ?? ($shippingData['postal_code'] ?? '');
                $country = $shippingData['country'] ?? 'Thailand';

                $shipAddress = $shippingData['ship_address'] ?? ($addrLine
                    ? trim(implode(', ', array_filter([$addrLine, $city, $province, $postalCode, $country])))
                    : '');

                // Never ship to a made-up phone number or address.
                if (trim((string) $shipPhone) === '' || trim($shipAddress) === '') {
                    throw ValidationException::withMessages([
                        'address' => ['Please enter a phone number and shipping address.'],
                    ]);
                }

                // If save_address requested, persist to address table
                if (!empty($shippingData['save_address']) || !empty($shippingData['saveAddress'])) {
                    Address::updateOrCreate(
                        ['member_id' => $member->member_id, 'is_default' => true],
                        [
                            'recipient_name' => $shipName,
                            'phone' => $shipPhone,
                            'address_line' => $addrLine ?: $shipAddress,
                            'district' => $city,
                            'province' => $province,
                            'postal_code' => $postalCode,
                            'is_default' => true,
                        ]
                    );
                }
            }

            // Create order. This is a simulated store (no real payment or shipping step),
            // so a placed order counts as delivered right away and its items can be reviewed.
            $order = Order::create([
                'member_id' => $member->member_id,
                'contact_email' => $contactEmail,
                'ship_name' => $shipName,
                'ship_phone' => $shipPhone,
                'ship_address' => $shipAddress,
                'total_amount' => $totalAmount,
                'status' => 'completed',
            ]);

            // Create order items
            foreach ($orderItemsData as $itemData) {
                $itemData['order_id'] = $order->order_id;
                OrderItem::insert($itemData);
            }

            // Clear member's cart
            CartItem::where('cart_id', $cart->cart_id)->delete();

            return $order->load(['items.variant.product.mainImage', 'items.variant.product.brand']);
        });
    }

    /**
     * Get order history for a member.
     */
    public function getOrdersForMember(int $memberId)
    {
        return Order::where('member_id', $memberId)
            ->with(['items.variant.product.mainImage', 'items.variant.product.brand'])
            ->orderBy('order_id', 'desc')
            ->get();
    }

    /**
     * Get single order detail for a member.
     */
    public function getOrderForMember(int $memberId, int $orderId): Order
    {
        return Order::where('order_id', $orderId)
            ->where('member_id', $memberId)
            ->with(['items.variant.product.mainImage', 'items.variant.product.brand'])
            ->firstOrFail();
    }
}
