<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderStatusService
{
    /**
     * Allowed order status transitions (current => next[]).
     */
    public const TRANSITIONS = [
        'pending' => ['paid', 'cancelled'],
        'paid' => ['shipped', 'cancelled'],
        'shipped' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    /**
     * Change an order's status (admin). Cancelling returns the stock.
     */
    public function updateStatus(int $orderId, string $status): Order
    {
        return DB::transaction(function () use ($orderId, $status) {
            $order = Order::where('order_id', $orderId)->lockForUpdate()->firstOrFail();

            if (!in_array($status, self::TRANSITIONS[$order->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => ["Cannot change order status from '{$order->status}' to '{$status}'."],
                ]);
            }

            if ($status === 'cancelled') {
                foreach (OrderItem::where('order_id', $order->order_id)->get() as $item) {
                    ProductVariant::where('variant_id', $item->variant_id)->increment('stock', $item->quantity);
                }
            }

            $order->update(['status' => $status]);

            return $order->load(['items.variant.product.mainImage', 'items.variant.product.brand']);
        });
    }

    /**
     * Member confirms a shipped order was received (shipped -> completed).
     */
    public function confirmReceived(int $memberId, int $orderId): Order
    {
        $order = Order::where('order_id', $orderId)
            ->where('member_id', $memberId)
            ->firstOrFail();

        if ($order->status !== 'shipped') {
            throw ValidationException::withMessages([
                'status' => ['Only shipped orders can be marked as received.'],
            ]);
        }

        return $this->updateStatus($orderId, 'completed');
    }
}
