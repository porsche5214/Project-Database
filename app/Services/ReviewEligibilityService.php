<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReviewEligibilityService
{
    /**
     * Check if a member is eligible to review or rate a product.
     * Eligible if and only if member has at least one order with status 'completed'
     * that contains a variant of the given product.
     */
    public function canReview(int $memberId, int $productId): bool
    {
        return DB::table('orders')
            ->join('order_item', 'orders.order_id', '=', 'order_item.order_id')
            ->join('product_variant', 'order_item.variant_id', '=', 'product_variant.variant_id')
            ->where('orders.member_id', $memberId)
            ->where('orders.status', 'completed')
            ->where('product_variant.product_id', $productId)
            ->exists();
    }
}
