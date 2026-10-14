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

        return view('wishlist.index', ['products' => $products]);
    }

    public function store(Request $request, int $id)
    {
        Product::findOrFail($id);
        // PK (member_id, product_id) กันซ้ำอยู่แล้ว: syncWithoutDetaching ไม่ insert ซ้ำ
        $request->user()->wishlist()->syncWithoutDetaching([$id]);

        return back()->with('status', 'เพิ่มลง Wishlist แล้ว');
    }

    public function destroy(Request $request, int $id)
    {
        $request->user()->wishlist()->detach($id);

        return back()->with('status', 'เอาออกจาก Wishlist แล้ว');
    }
}
