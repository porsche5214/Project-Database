<?php

namespace AppHttpControllersApi;

use AppHttpControllersController;
use AppHttpResourcesProductResource;
use AppModelsProduct;
use IlluminateHttpRequest;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $products = $request->user()->wishlist()
            ->with(['brand', 'mainImage', 'variants'])
            ->withAvg('ratings', 'score')
            ->orderByDesc('wishlist.added_at')
            ->get();

        return response()->json([
            'ids' => $products->pluck('product_id')->all(),
            'products' => ProductResource::collection($products),
        ]);
    }

    public function store(Request $request, int $id)
    {
        Product::findOrFail($id);
        $request->user()->wishlist()->syncWithoutDetaching([$id]);

        return response()->json([
            'message' => 'Added to wishlist',
            'ids' => $request->user()->wishlist()->pluck('product.product_id')->all(),
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $request->user()->wishlist()->detach($id);

        return response()->json([
            'message' => 'Removed from wishlist',
            'ids' => $request->user()->wishlist()->pluck('product.product_id')->all(),
        ]);
    }

    public function toggle(Request $request, int $id)
    {
        Product::findOrFail($id);
        $exists = $request->user()->wishlist()->where('wishlist.product_id', $id)->exists();

        if ($exists) {
            $request->user()->wishlist()->detach($id);
        } else {
            $request->user()->wishlist()->syncWithoutDetaching([$id]);
        }

        return response()->json([
            'message' => $exists ? 'Removed from wishlist' : 'Added to wishlist',
            'in_wishlist' => !$exists,
            'ids' => $request->user()->wishlist()->pluck('product.product_id')->all(),
        ]);
    }
}
