<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array matching frontend Product type.
     */
    public function toArray(Request $request): array
    {
        $colors = $this->variants ? $this->variants->pluck('color')->unique()->values()->all() : [];
        $sizes = $this->variants ? $this->variants->pluck('size')->unique()->values()->all() : [];

        $avgRating = $this->ratings_avg_score !== null
            ? (float) round((float) $this->ratings_avg_score, 1)
            : (float) round((float) ($this->ratings()->avg('score') ?? 0), 1);

        $reviewsCount = $this->ratings_count !== null
            ? (int) $this->ratings_count
            : (int) $this->ratings()->count();

        return [
            'id' => (int) $this->product_id,
            'name' => (string) $this->name,
            'brand' => (string) ($this->brand?->name ?? 'NaeMak'),
            'price' => (float) $this->base_price,
            'category' => (string) ($this->category?->name ?? 'General'),
            'gender' => (string) ($this->gender ?? 'unisex'),
            'rating' => $avgRating,
            'reviews' => $reviewsCount,
            'image' => $this->mainImage?->image_url ?: '/images/tee-white.jpg',
            'badge' => $avgRating >= 4.8 ? 'BESTSELLER' : null,
            'colors' => $colors ?: ['Black'],
            'sizes' => $sizes ?: ['M'],
        ];
    }
}
