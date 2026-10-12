<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $products = $request->user()->wishlist()
            ->with(['brand', 'mainImage'])
            ->withAvg('ratings', 'score')
            ->orderByDesc('wishlist.added_at')
            ->get();

        if ($request->is('api/*') || $request->wantsJson()) {
            return response()->json($products);
        }

        return view('wishlist.index', ['products' => $products]);
    }

    public function store(Request $request, int $id)
    {
        Product::findOrFail($id);
        // PK (member_id, product_id) กันซ้ำอยู่แล้ว: syncWithoutDetaching ไม่ insert ซ้ำ
        $request->user()->wishlist()->syncWithoutDetaching([$id]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'เพิ่มลง Wishlist แล้ว'], 200);
        }

        return back()->with('status', 'เพิ่มลง Wishlist แล้ว');
    }

    public function destroy(Request $request, int $id)
    {
        $request->user()->wishlist()->detach($id);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'เอาออกจาก Wishlist แล้ว'], 200);
        }

        return back()->with('status', 'เอาออกจาก Wishlist แล้ว');
    }
}
