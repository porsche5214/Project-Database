<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // 1 คนมีได้ 1 รีวิวต่อสินค้า ถ้ามีอยู่แล้วให้อัปเดตแทนการสร้างซ้ำ
    public function store(Request $request, int $id)
    {
        Product::findOrFail($id);
        $data = $request->validate(['comment' => ['required', 'string', 'max:1000']]);

        $review = Review::updateOrCreate(
            ['member_id' => $request->user()->member_id, 'product_id' => $id],
            ['comment' => $data['comment']]
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => 'บันทึกรีวิวแล้ว', 'review' => $review], 200);
        }

        return back()->with('status', 'บันทึกรีวิวแล้ว');
    }

    // แก้ไขรีวิวของตัวเอง หรือ admin
    public function update(Request $request, int $id)
    {
        $review = Review::findOrFail($id);
        $member = $request->user();
        abort_unless((int) $review->member_id === (int) $member->member_id || $member->isAdmin(), 403);

        $data = $request->validate(['comment' => ['required', 'string', 'max:1000']]);
        $review->update(['comment' => $data['comment']]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'แก้ไขรีวิวแล้ว', 'review' => $review], 200);
        }

        return back()->with('status', 'แก้ไขรีวิวแล้ว');
    }

    // ลบได้เฉพาะรีวิวของตัวเอง หรือ admin
    public function destroy(Request $request, int $id)
    {
        $review = Review::findOrFail($id);
        $member = $request->user();
        abort_unless((int) $review->member_id === (int) $member->member_id || $member->isAdmin(), 403);

        $review->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'ลบรีวิวแล้ว'], 200);
        }

        return back()->with('status', 'ลบรีวิวแล้ว');
    }
}
