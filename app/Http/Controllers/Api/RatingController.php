<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Rating;
use App\Services\ReviewEligibilityService;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    protected ReviewEligibilityService $eligibilityService;

    public function __construct(ReviewEligibilityService $eligibilityService)
    {
        $this->eligibilityService = $eligibilityService;
    }

    public function store(Request $request, int $id)
    {
        Product::findOrFail($id);
        $member = $request->user();

        if (!$this->eligibilityService->canReview($member->member_id, $id)) {
            return response()->json([
                'message' => 'Only verified buyers with a completed order can rate this product.',
            ], 403);
        }

        $validated = $request->validate([
            'score' => ['required', 'integer', 'between:1,5'],
        ]);

        Rating::updateOrInsert(
            ['member_id' => $member->member_id, 'product_id' => $id],
            ['score' => (int) $validated['score'], 'rated_at' => now()]
        );

        $newAvg = (float) round((float) Rating::where('product_id', $id)->avg('score'), 1);
        $count = Rating::where('product_id', $id)->count();

        return response()->json([
            'message' => 'Rating submitted successfully',
            'score' => (int) $validated['score'],
            'average_rating' => $newAvg,
            'ratings_count' => $count,
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        Product::findOrFail($id);
        Rating::where('member_id', $request->user()->member_id)
            ->where('product_id', $id)
            ->delete();

        return response()->json(['message' => 'Rating removed successfully']);
    }
}
