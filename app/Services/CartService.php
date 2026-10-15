<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Get or create a cart for the member.
     */
    
    /**
     * Find a variant ID by product_id, color, and size (with fallback).
     */
    public function findVariantId(int $productId, ?string $color = null, ?string $size = null): ?int
    {
        $query = ProductVariant::where('product_id', $productId);
        if ($color) {
            $query->where('color', $color);
        }
        if ($size) {
            $query->where('size', $size);
        }
        $variant = $query->first();
        if (!$variant) {
            $variant = ProductVariant::where('product_id', $productId)->first();
        }
        return $variant?->variant_id;
    }

    public function getCartForMember(int $memberId): Cart
    {
        return Cart::firstOrCreate(['member_id' => $memberId]);
    }

    /**
     * Get full cart details including calculated shipping and totals.
     */
    public function getCartDetails(int $memberId): array
    {
        $cart = $this->getCartForMember($memberId);
        $items = CartItem::where('cart_id', $cart->cart_id)
            ->with(['variant.product.mainImage', 'variant.product.brand'])
            ->get();

        $subtotal = 0;
        $formattedItems = [];

        foreach ($items as $item) {
            $variant = $item->variant;
            if (!$variant) continue;

            $product = $variant->product;
            $lineTotal = $item->quantity * (float) $variant->price;
            $subtotal += $lineTotal;

            $formattedItems[] = [
                'variant_id' => (int) $variant->variant_id,
                'product_id' => $product ? (int) $product->product_id : 0,
                'name' => $product ? (string) $product->name : 'Product',
                'brand' => $product?->brand ? (string) $product->brand->name : 'NaeMak',
                'image' => $product?->mainImage?->image_url ?: '/images/tee-white.jpg',
                'color' => (string) $variant->color,
                'size' => (string) $variant->size,
                'price' => (float) $variant->price,
                'quantity' => (int) $item->quantity,
                'total' => $lineTotal,
                'stock' => (int) $variant->stock,
            ];
        }

        // Server-side shipping fee: ฿150 if subtotal < ฿2500, else 0 (Default 3)
        $shipping = ($subtotal > 0 && $subtotal < 2500) ? 150.0 : 0.0;
        $total = $subtotal + $shipping;

        return [
            'cart_id' => $cart->cart_id,
            'items' => $formattedItems,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $total,
        ];
    }

    /**
     * Add an item to the cart. If variant exists, increment quantity.
     */
    public function addItem(int $memberId, int $variantId, int $quantity): array
    {
        $variant = ProductVariant::findOrFail($variantId);
        $cart = $this->getCartForMember($memberId);

        $cartItem = CartItem::where('cart_id', $cart->cart_id)
            ->where('variant_id', $variantId)
            ->first();

        $newQuantity = $quantity;
        if ($cartItem) {
            $newQuantity += $cartItem->quantity;
        }

        if ($variant->stock < $newQuantity) {
            throw ValidationException::withMessages([
                'quantity' => ['Not enough stock.'],
            ]);
        }

        if ($cartItem) {
            CartItem::where('cart_id', $cart->cart_id)
                ->where('variant_id', $variantId)
                ->update(['quantity' => $newQuantity]);
        } else {
            CartItem::insert([
                'cart_id' => $cart->cart_id,
                'variant_id' => $variantId,
                'quantity' => $quantity,
            ]);
        }

        return $this->getCartDetails($memberId);
    }

    /**
     * Update quantity of an item in the cart.
     */
    public function updateQuantity(int $memberId, int $variantId, int $quantity): array
    {
        $cart = $this->getCartForMember($memberId);
        $cartItem = CartItem::where('cart_id', $cart->cart_id)
            ->where('variant_id', $variantId)
            ->first();

        if (!$cartItem) {
            throw ValidationException::withMessages([
                'msg' => ['Item not in cart.'],
            ]);
        }

        $variant = ProductVariant::findOrFail($variantId);
        if ($variant->stock < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['Not enough stock.'],
            ]);
        }

        CartItem::where('cart_id', $cart->cart_id)
            ->where('variant_id', $variantId)
            ->update(['quantity' => $quantity]);

        return $this->getCartDetails($memberId);
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(int $memberId, int $variantId): array
    {
        $cart = $this->getCartForMember($memberId);
        CartItem::where('cart_id', $cart->cart_id)
            ->where('variant_id', $variantId)
            ->delete();

        return $this->getCartDetails($memberId);
    }

    /**
     * Clear all items in the member's cart.
     */
    public function clearCart(int $memberId): void
    {
        $cart = $this->getCartForMember($memberId);
        CartItem::where('cart_id', $cart->cart_id)->delete();
    }
}
