<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Rating;
use App\Models\Review;
use App\Services\ReviewEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    protected ReviewEligibilityService $eligibilityService;

    public function __construct(ReviewEligibilityService $eligibilityService)
    {
        $this->eligibilityService = $eligibilityService;
    }

    public function index(int $id)
    {
        $product = Product::findOrFail($id);
        $reviews = Review::where('product_id', $id)
            ->with('member')
            ->orderBy('review_id', 'desc')
            ->get()
            ->map(function ($rev) use ($id) {
                $rating = Rating::where('member_id', $rev->member_id)
                    ->where('product_id', $id)
                    ->value('score') ?? 5;

                [$title, $text] = $rev->splitComment();

                $reviewer = $rev->member
                    ? trim(($rev->member->first_name ?? '') . ' ' . (Str::substr($rev->member->last_name ?? '', 0, 1) . '.'))
                    : 'Member';
                if (empty(trim($reviewer)) || $reviewer === '.') {
                    $reviewer = $rev->member?->username ?? 'Member';
                }

                return [
                    'id' => (string) $rev->review_id,
                    'productId' => (int) $rev->product_id,
                    'reviewer' => $reviewer,
                    'date' => $rev->created_at ? $rev->created_at->format('d M Y') : '',
                    'title' => $title,
                    'text' => $text,
                    'rating' => (int) $rating,
                ];
            });

        return response()->json([
            'reviews' => $reviews,
            'total' => $reviews->count(),
        ]);
    }

    public function store(Request $request, int $id)
    {
        Product::findOrFail($id);
        $member = $request->user();

        // Eligibility Check: Must have a completed order containing this product
        if (!$this->eligibilityService->canReview($member->member_id, $id)) {
            return response()->json([
                'message' => 'Only verified buyers with a completed order can leave a review.',
            ], 403);
        }

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
            'text' => ['nullable', 'string', 'max:1000'],
            'title' => ['nullable', 'string', 'max:150'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'score' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $text = $validated['comment'] ?? ($validated['text'] ?? '');
        $title = $validated['title'] ?? '';

        if (empty(trim($text))) {
            return response()->json(['message' => 'The review text cannot be empty.'], 422);
        }

        $commentToStore = !empty($title) ? "[{$title}] {$text}" : $text;

        $review = Review::updateOrCreate(
            ['member_id' => $member->member_id, 'product_id' => $id],
            ['comment' => $commentToStore]
        );

        $score = $validated['rating'] ?? ($validated['score'] ?? null);
        if ($score !== null) {
            Rating::updateOrInsert(
                ['member_id' => $member->member_id, 'product_id' => $id],
                ['score' => (int) $score, 'rated_at' => now()]
            );
        }

        $newAvg = (float) round((float) Rating::where('product_id', $id)->avg('score'), 1);
        $reviewsCount = Review::where('product_id', $id)->count();

        return response()->json([
            'message' => 'Review published successfully',
            'review' => [
                'id' => (string) $review->review_id,
                'productId' => $id,
                'reviewer' => trim("{$member->first_name} " . Str::substr($member->last_name ?? '', 0, 1) . '.') ?: $member->username,
                'date' => date('d M Y'),
                'title' => $title,
                'text' => $text,
                'rating' => (int) ($score ?: 5),
            ],
            'average_rating' => $newAvg,
            'reviews_count' => $reviewsCount,
        ], 201);
    }

    public function destroy(Request $request, int $id)
    {
        $review = Review::findOrFail($id);
        $member = $request->user();

        if ((int) $review->member_id !== (int) $member->member_id && !$member->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }

    public function eligibility(Request $request, int $id)
    {
        $member = $request->user();
        $canReview = $this->eligibilityService->canReview($member->member_id, $id);
        $myScore = Rating::where('member_id', $member->member_id)->where('product_id', $id)->value('score');
        $myReview = Review::where('member_id', $member->member_id)->where('product_id', $id)->first();

        return response()->json([
            'can_review' => $canReview,
            'my_score' => $myScore ? (int) $myScore : null,
            'my_review' => $myReview ? [
                'id' => (string) $myReview->review_id,
                'comment' => $myReview->comment,
            ] : null,
        ]);
    }
}
