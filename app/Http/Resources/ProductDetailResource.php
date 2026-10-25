<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array with variants and reviews.
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        $colors = $this->variants ? $this->variants->pluck('color')->unique()->values()->all() : [];
        $sizes = $this->variants ? $this->variants->pluck('size')->unique()->values()->all() : [];

        $avgRating = $this->ratings_avg_score !== null
            ? (float) round((float) $this->ratings_avg_score, 1)
            : (float) round((float) ($this->ratings()->avg('score') ?? 0), 1);

        $reviewsCount = $this->ratings_count !== null
            ? (int) $this->ratings_count
            : (int) $this->ratings()->count();

        $myScore = $user
            ? $this->ratings()->where('member_id', $user->member_id)->value('score')
            : null;

        $inWishlist = $user
            ? $user->wishlist()->where('wishlist.product_id', $this->product_id)->exists()
            : false;

        $images = $this->images ? $this->images->pluck('image_url')->all() : [];
        $mainImage = $this->mainImage?->image_url ?: ($images[0] ?? '/images/tee-white.jpg');

        $reviews = $this->reviews ? $this->reviews->map(function ($rev) {
            $reviewerName = $rev->member?->username ?? 'Member';
            $ratingScore = $this->ratings()->where('member_id', $rev->member_id)->value('score') ?? 5;
            return [
                'id' => (string) $rev->review_id,
                'productId' => (int) $rev->product_id,
                'reviewer' => $reviewerName,
                'date' => $rev->created_at ? $rev->created_at->format('d M Y') : '',
                'title' => '',
                'text' => $rev->comment,
                'rating' => (int) $ratingScore,
            ];
        })->all() : [];

        $variants = $this->variants ? $this->variants->map(function ($v) {
            return [
                'variant_id' => (int) $v->variant_id,
                'product_id' => (int) $v->product_id,
                'color' => (string) $v->color,
                'size' => (string) $v->size,
                'sku' => (string) $v->sku,
                'price' => (float) $v->price,
                'stock' => (int) $v->stock,
            ];
        })->all() : [];

        return [
            'id' => (int) $this->product_id,
            'name' => (string) $this->name,
            'description' => (string) ($this->description ?? ''),
            'brand' => (string) ($this->brand?->name ?? 'NaeMak'),
            'price' => (float) $this->base_price,
            'category' => (string) ($this->category?->name ?? 'General'),
            'gender' => (string) ($this->gender ?? 'unisex'),
            'rating' => $avgRating,
            'reviews' => $reviewsCount,
            'image' => $mainImage,
            'images' => $images ?: [$mainImage],
            'colors' => $colors ?: ['Black'],
            'sizes' => $sizes ?: ['M'],
            'variants' => $variants,
            'reviews_list' => $reviews,
            'myScore' => $myScore,
            'inWishlist' => (bool) $inWishlist,
        ];
    }
}
