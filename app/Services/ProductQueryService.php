<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductQueryService
{
    public function buildQuery(Request $request): Builder
    {
        $query = Product::with(['category', 'brand', 'mainImage', 'variants'])
            ->withAvg('ratings', 'score')
            ->withCount('ratings');

        // Category filter (support ID or name)
        if ($request->filled('category')) {
            $cat = $request->input('category');
            if (is_numeric($cat)) {
                $categoryIds = Category::where('parent_category_id', $cat)->pluck('category_id')->push((int) $cat);
                $query->whereIn('category_id', $categoryIds);
            } else {
                $cats = is_array($cat) ? $cat : explode(',', $cat);
                $query->whereHas('category', fn ($q) => $q->whereIn('name', $cats));
            }
        }

        // Brand filter (support ID or name)
        if ($request->filled('brand')) {
            $b = $request->input('brand');
            if (is_numeric($b)) {
                $query->where('brand_id', $b);
            } else {
                $brands = is_array($b) ? $b : explode(',', $b);
                $query->whereHas('brand', fn ($q) => $q->whereIn('name', $brands));
            }
        }

        // Gender filter
        if ($request->filled('gender')) {
            $g = $request->input('gender');
            $genders = is_array($g) ? $g : explode(',', $g);
            $query->where(function ($q) use ($genders) {
                $q->whereIn('gender', $genders)->orWhere('gender', 'unisex');
            });
        }

        // Size filter (via variants)
        if ($request->filled('size')) {
            $s = $request->input('size');
            $sizes = is_array($s) ? $s : explode(',', $s);
            $query->whereHas('variants', fn ($q) => $q->whereIn('size', $sizes));
        }

        // Color filter (via variants)
        if ($request->filled('color')) {
            $c = $request->input('color');
            $colors = is_array($c) ? $c : explode(',', $c);
            $query->whereHas('variants', fn ($q) => $q->whereIn('color', $colors));
        }

        // Price range
        if ($request->filled('min_price')) {
            $query->where('base_price', '>=', (float) $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('base_price', '<=', (float) $request->input('max_price'));
        }

        // Search query
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$q}%"));
            });
        }

        // In stock
        if ($request->boolean('in_stock')) {
            $query->whereHas('variants', fn ($q) => $q->where('stock', '>', 0));
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'price_asc', 'price-asc' => $query->orderBy('base_price'),
            'price_desc', 'price-desc' => $query->orderByDesc('base_price'),
            'rating' => $query->orderByDesc('ratings_avg_score'),
            'recommended' => $query->orderByDesc('ratings_avg_score')->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at')->orderByDesc('product_id'),
        };

        return $query;
    }
}
