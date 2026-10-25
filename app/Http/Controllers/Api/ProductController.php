<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductQueryService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ProductQueryService $queryService;

    public function __construct(ProductQueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    public function index(Request $request)
    {
        $query = $this->queryService->buildQuery($request);
        $products = $query->get();

        return response()->json([
            'data' => ProductResource::collection($products),
            'total' => $products->count(),
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

        return response()->json(new ProductDetailResource($product));
    }
}
