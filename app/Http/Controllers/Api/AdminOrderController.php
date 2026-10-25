<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminOrderController extends Controller
{
    protected OrderStatusService $statusService;

    public function __construct(OrderStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $orders = Order::with(['items.variant.product.mainImage', 'items.variant.product.brand'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('order_id')
            ->get();

        return response()->json([
            'orders' => OrderResource::collection($orders),
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(OrderStatusService::TRANSITIONS))],
        ]);

        $order = $this->statusService->updateStatus($id, $validated['status']);

        return response()->json([
            'message' => 'Order status updated',
            'order' => new OrderResource($order),
        ]);
    }
}
