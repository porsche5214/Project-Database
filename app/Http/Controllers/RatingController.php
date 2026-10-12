<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    // 1 คนให้คะแนนได้ 1 ครั้งต่อสินค้า ให้ใหม่ = แก้คะแนนเดิม
    public function store(Request $request, int $id)
    {
        Product::findOrFail($id);
        $data = $request->validate(['score' => ['required', 'integer', 'between:1,5']]);

        Rating::updateOrInsert(
            ['member_id' => $request->user()->member_id, 'product_id' => $id],
            ['score' => $data['score'], 'rated_at' => now()],
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => 'บันทึกคะแนนแล้ว'], 200);
        }

        return back()->with('status', 'บันทึกคะแนนแล้ว');
    }

    public function destroy(Request $request, int $id)
    {
        Product::findOrFail($id);
        Rating::where('member_id', $request->user()->member_id)
            ->where('product_id', $id)
            ->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'ลบคะแนนแล้ว'], 200);
        }

        return back()->with('status', 'ลบคะแนนแล้ว');
    }
}
