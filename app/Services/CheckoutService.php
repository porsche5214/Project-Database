<?php

namespace AppServices;

use AppModelsAddress;
use AppModelsCart;
use AppModelsCartItem;
use AppModelsMember;
use AppModelsOrder;
use AppModelsOrderItem;
use AppModelsProductVariant;
use IlluminateSupportFacadesDB;
use IlluminateValidationValidationException;

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

                if (!$variant || $variant->stock < $item->quantity) {
                    throw new \Exception('Not enough stock for variant ID: ' . $item->variant_id);
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

                $shipPhone = $shippingData['ship_phone'] ?? ($shippingData['phone'] ?? $member->phone ?? '0000000000');

                $addrLine = $shippingData['address'] ?? ($shippingData['address_line'] ?? '');
                $city = $shippingData['city'] ?? ($shippingData['district'] ?? '');
                $province = $shippingData['province'] ?? '';
                $postalCode = $shippingData['postalCode'] ?? ($shippingData['postal_code'] ?? '');
                $country = $shippingData['country'] ?? 'Thailand';

                $shipAddress = $shippingData['ship_address'] ?? trim(implode(', ', array_filter([$addrLine, $city, $province, $postalCode, $country])));
                if (empty($shipAddress)) {
                    $shipAddress = 'Bangkok, Thailand';
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

            // Create order
            $order = Order::create([
                'member_id' => $member->member_id,
                'contact_email' => $contactEmail,
                'ship_name' => $shipName,
                'ship_phone' => $shipPhone,
                'ship_address' => $shipAddress,
                'total_amount' => $totalAmount,
                'status' => 'pending',
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
