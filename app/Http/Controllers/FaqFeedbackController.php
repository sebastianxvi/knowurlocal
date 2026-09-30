<?php

namespace App\Http\Controllers;

use App\Models\ChatbotLog;
use App\Models\Faq;
use App\Models\FaqFeedback;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FaqFeedbackController extends Controller
{
    /** Save or update a user's rating for an exact answered FAQ response. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'chatbot_log_id' => ['required', 'integer', 'min:1'],
            'rating' => ['required', Rule::in(['helpful', 'not_helpful'])],
            'reason' => ['nullable', Rule::in(['incorrect', 'incomplete', 'outdated', 'unclear', 'attachments', 'other'])],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $log = ChatbotLog::query()
            ->whereKey($validated['chatbot_log_id'])
            ->where('user_id', $request->user()->id)
            ->where('outcome', 'answered')
            ->whereNotNull('faq_id')
            ->firstOrFail();

        $feedback = FaqFeedback::query()->updateOrCreate(
            [
                'chatbot_log_id' => $log->id,
                'user_id' => $request->user()->id,
            ],
            [
                'faq_id' => $log->faq_id,
                'rating' => $validated['rating'],
                'reason' => $validated['rating'] === 'not_helpful' ? ($validated['reason'] ?? null) : null,
                'comment' => $validated['rating'] === 'not_helpful'
                    ? (isset($validated['comment']) ? trim($validated['comment']) : null)
                    : null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Thank you for helping us improve our FAQ answers.',
            'rating' => $feedback->rating,
        ]);
    }

    /** Return a paginated, FAQ-level view of individual ratings for the admin modal. */
    public function forFaq(Request $request, Faq $faq)
    {
        $validated = $request->validate([
            'rating' => ['nullable', Rule::in(['all', 'helpful', 'not_helpful'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $counts = FaqFeedback::query()
            ->where('faq_id', $faq->id)
            ->selectRaw("COUNT(*) AS total, SUM(CASE WHEN rating = 'helpful' THEN 1 ELSE 0 END) AS likes, SUM(CASE WHEN rating = 'not_helpful' THEN 1 ELSE 0 END) AS dislikes")
            ->first();

        $total = (int) ($counts->total ?? 0);
        $likes = (int) ($counts->likes ?? 0);
        $dislikes = (int) ($counts->dislikes ?? 0);
        $minimumRatings = (int) config('faq_feedback.minimum_ratings_for_review', 5);
        $priorityMinimum = (int) config('faq_feedback.priority_minimum_ratings', 10);
        $negativeRate = $total > 0 ? $dislikes / $total : 0;
        $needsReview = $total >= $minimumRatings && $dislikes > $likes;
        $priorityReview = $total >= $priorityMinimum
            && $negativeRate >= (float) config('faq_feedback.priority_negative_rate', 0.60);

        $rating = $validated['rating'] ?? 'all';
        $feedbackQuery = FaqFeedback::query()
            ->where('faq_id', $faq->id)
            ->select(['id', 'rating', 'reason', 'comment', 'created_at']);

        if ($rating !== 'all') {
            $feedbackQuery->where('rating', $rating);
        }

        $feedback = $feedbackQuery
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            'faq' => [
                'id' => $faq->id,
                'question' => $faq->question,
                'agency' => $faq->agency?->agency_name,
                'likes' => $likes,
                'dislikes' => $dislikes,
                'total' => $total,
                'negative_rate' => round($negativeRate * 100, 1),
                'needs_review' => $needsReview,
                'priority_review' => $priorityReview,
                'minimum_ratings' => $minimumRatings,
            ],
            'feedback' => [
                'data' => $feedback->getCollection()->map(fn (FaqFeedback $item) => [
                    'id' => $item->id,
                    'rating' => $item->rating,
                    'reason' => $item->reason,
                    'comment' => $item->comment,
                    'submitted_at' => $item->created_at?->format('M j, Y · g:i A'),
                ])->values(),
                'current_page' => $feedback->currentPage(),
                'last_page' => $feedback->lastPage(),
                'total' => $feedback->total(),
            ],
        ]);
    }
}
