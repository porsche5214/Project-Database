<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, int $id)
    {
        Product::findOrFail($id);
        $data = $request->validate(['comment' => ['required', 'string', 'max:1000']]);

        Review::create([
            'member_id' => $request->user()->member_id,
            'product_id' => $id,
            'comment' => $data['comment'],
        ]);

        return back()->with('status', 'เพิ่มรีวิวแล้ว');
    }

    // ลบได้เฉพาะรีวิวของตัวเอง หรือ admin
    public function destroy(Request $request, int $id)
    {
        $review = Review::findOrFail($id);
        $member = $request->user();
        abort_unless((int) $review->member_id === (int) $member->member_id || $member->isAdmin(), 403);

        $review->delete();

        return back()->with('status', 'ลบรีวิวแล้ว');
    }
}
