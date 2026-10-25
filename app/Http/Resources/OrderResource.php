<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $orderDate = $this->order_date ? strtotime($this->order_date) : time();
        $orderNumber = 'NM-' . date('ymd', $orderDate) . '-' . sprintf('%04d', $this->order_id);
        $formattedDate = date('d M Y', $orderDate);

        $items = $this->items->map(function ($item) {
            $variant = $item->variant;
            $product = $variant?->product;

            return [
                'variant_id' => (int) $item->variant_id,
                'product_id' => $product ? (int) $product->product_id : 0,
                'name' => $product ? (string) $product->name : 'Product',
                'brand' => $product?->brand ? (string) $product->brand->name : 'NaeMak',
                'image' => $product?->mainImage?->image_url ?: '/images/tee-white.jpg',
                'color' => $variant ? (string) $variant->color : '',
                'size' => $variant ? (string) $variant->size : '',
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) ($item->quantity * $item->unit_price),
            ];
        });

        return [
            'order_id' => (int) $this->order_id,
            'order_number' => $orderNumber,
            'order_date' => $this->order_date,
            'formatted_date' => $formattedDate,
            'status' => (string) $this->status,
            'total_amount' => (float) $this->total_amount,
            'contact_email' => (string) $this->contact_email,
            'ship_name' => (string) $this->ship_name,
            'ship_phone' => (string) $this->ship_phone,
            'ship_address' => (string) $this->ship_address,
            'items' => $items,
        ];
    }
}
