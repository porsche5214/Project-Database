<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    // GET /products?category=1&brand=2&gender=male&q=เสื้อ&sort=price_asc
    public function index(Request $request)
    {
        $filters = $request->validate([
            'category' => ['nullable', 'integer'],
            'brand' => ['nullable', 'integer'],
            'gender' => ['nullable', 'in:male,female,unisex'],
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:newest,price_asc,price_desc,rating'],
            'in_stock' => ['nullable', 'boolean'],
        ]);

        $query = Product::with(['category', 'brand', 'mainImage'])
            ->withAvg('ratings', 'score')
            ->withCount('ratings');

        if (! empty($filters['category'])) {
            // เลือกหมวดแม่ (เช่น Top) ให้รวมหมวดลูก (T-shirt, Hoodie, ...) ด้วย
            $ids = Category::where('parent_category_id', $filters['category'])->pluck('category_id')
                ->push((int) $filters['category']);
            $query->whereIn('category_id', $ids);
        }
        if (! empty($filters['brand'])) {
            $query->where('brand_id', $filters['brand']);
        }
        if (! empty($filters['gender'])) {
            // สินค้า unisex แสดงทั้งตอนกรองชายและหญิง
            $query->whereIn('gender', [$filters['gender'], 'unisex']);
        }
        if (! empty($filters['q'])) {
            $query->where('name', 'like', '%'.$filters['q'].'%');
        }
        if ($request->boolean('in_stock')) {
            $query->whereHas('variants', fn ($q) => $q->where('stock', '>', 0));
        }

        match ($filters['sort'] ?? 'newest') {
            'price_asc' => $query->orderBy('base_price'),
            'price_desc' => $query->orderByDesc('base_price'),
            'rating' => $query->orderByDesc('ratings_avg_score'),
            default => $query->orderByDesc('created_at')->orderByDesc('product_id'),
        };

        if ($request->is('api/*') || $request->wantsJson()) {
            return response()->json($query->paginate(12)->withQueryString());
        }

        return view('products.index', [
            'products' => $query->paginate(12)->withQueryString(),
            'categories' => Category::with('children')->whereNull('parent_category_id')->orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'filters' => $filters,
            'wishlistIds' => $this->wishlistIds(),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $product = Product::with([
            'category.parent',
            'brand',
            'images',
            'variants' => fn ($q) => $q->orderBy('color')->orderBy('variant_id'),
            'reviews' => fn ($q) => $q->with('member:member_id,username')->orderByDesc('created_at'),
        ])
            ->withAvg('ratings', 'score')
            ->withCount('ratings')
            ->findOrFail($id);

        if ($request->is('api/*') || $request->wantsJson()) {
            return response()->json($product);
        }

        $member = Auth::user();

        return view('products.show', [
            'product' => $product,
            // ข้อมูลให้ JS ใช้เลือกสี -> ไซซ์
            'variantData' => $product->variants->map(fn ($v) => [
                'id' => $v->variant_id,
                'color' => $v->color,
                'size' => $v->size,
                'price' => (float) $v->price,
                'stock' => $v->stock,
            ])->values(),
            'myScore' => $member ? $product->ratings()->where('member_id', $member->member_id)->value('score') : null,
            'inWishlist' => in_array($product->product_id, $this->wishlistIds()),
            // breakdown จำนวนคนให้ 5,4,3,2,1 ดาว
            'scoreCounts' => $product->ratings()->selectRaw('score, COUNT(*) as total')->groupBy('score')->pluck('total', 'score'),
        ]);
    }

    private function wishlistIds(): array
    {
        $member = Auth::user();

        return $member ? $member->wishlist()->pluck('product.product_id')->all() : [];
    }
}
