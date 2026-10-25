<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index(Request $request)
    {
        $details = $this->cartService->getCartDetails($request->user()->member_id);
        return response()->json($details);
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'variant_id' => ['nullable', 'integer', 'exists:product_variant,variant_id'],
            'product_id' => ['nullable', 'integer', 'exists:product,product_id'],
            'color' => ['nullable', 'string'],
            'size' => ['nullable', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $variantId = $validated['variant_id'] ?? null;
        if (!$variantId && !empty($validated['product_id'])) {
            $variantId = $this->cartService->findVariantId(
                (int) $validated['product_id'],
                $validated['color'] ?? null,
                $validated['size'] ?? null
            );
        }

        if (!$variantId) {
            return response()->json(['message' => 'Variant not found'], 422);
        }

        $details = $this->cartService->addItem(
            $request->user()->member_id,
            (int) $variantId,
            (int) $validated['quantity']
        );

        return response()->json([
            'message' => 'Item added to bag',
            'cart' => $details,
        ]);
    }

    public function update(Request $request, $variant_id)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'product_id' => ['nullable', 'integer'],
            'color' => ['nullable', 'string'],
            'size' => ['nullable', 'string'],
        ]);

        $vId = (int) $variant_id;
        if ($vId <= 0 && !empty($validated['product_id'])) {
            $vId = (int) $this->cartService->findVariantId(
                (int) $validated['product_id'],
                $validated['color'] ?? null,
                $validated['size'] ?? null
            );
        }

        $details = $this->cartService->updateQuantity(
            $request->user()->member_id,
            $vId,
            (int) $validated['quantity']
        );

        return response()->json([
            'message' => 'Cart updated',
            'cart' => $details,
        ]);
    }

    public function remove(Request $request, $variant_id)
    {
        $vId = (int) $variant_id;
        if ($vId <= 0 && $request->has('product_id')) {
            $vId = (int) $this->cartService->findVariantId(
                (int) $request->input('product_id'),
                $request->input('color'),
                $request->input('size')
            );
        }

        $details = $this->cartService->removeItem($request->user()->member_id, $vId);

        return response()->json([
            'message' => 'Item removed from bag',
            'cart' => $details,
        ]);
    }

    public function clear(Request $request)
    {
        $this->cartService->clearCart($request->user()->member_id);

        return response()->json([
            'message' => 'Cart cleared',
            'cart' => $this->cartService->getCartDetails($request->user()->member_id),
        ]);
    }
}
