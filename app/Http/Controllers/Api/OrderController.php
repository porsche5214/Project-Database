<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Services\CheckoutService;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected CheckoutService $checkoutService;

    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    public function index(Request $request)
    {
        $orders = $this->checkoutService->getOrdersForMember($request->user()->member_id);
        return response()->json([
            'orders' => OrderResource::collection($orders),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $order = $this->checkoutService->getOrderForMember($request->user()->member_id, $id);
        return response()->json([
            'order' => new OrderResource($order),
        ]);
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'address_id' => ['nullable', 'integer', 'exists:address,address_id'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'first_name' => ['nullable', 'string'],
            'last_name' => ['nullable', 'string'],
            'firstName' => ['nullable', 'string'],
            'lastName' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'province' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string'],
            'postalCode' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'save_address' => ['nullable', 'boolean'],
            'saveAddress' => ['nullable', 'boolean'],
            'payment_method' => ['nullable', 'string'],
        ]);

        try {
            $order = $this->checkoutService->checkout(
                $request->user()->member_id,
                $validated,
                calculateShipping: true
            );

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => new OrderResource($order),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function confirmReceived(Request $request, int $id, OrderStatusService $statusService)
    {
        $order = $statusService->confirmReceived($request->user()->member_id, $id);

        return response()->json([
            'message' => 'Order marked as received',
            'order' => new OrderResource($order),
        ]);
    }
}
