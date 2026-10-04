<?php

namespace App\Http\Controllers;

use App\Models\ChatbotLog;
use App\Models\Faq;
use App\Models\FaqFeedback;
use App\Models\FaqVersion;
use App\Services\AuditLogService;
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
            ->whereNotNull('faq_version_id')
            ->whereHas('faqVersion')
            ->firstOrFail();

        $feedback = FaqFeedback::query()->updateOrCreate(
            [
                'chatbot_log_id' => $log->id,
                'user_id' => $request->user()->id,
            ],
            [
                'faq_id' => $log->faq_id,
                'faq_version_id' => $log->faq_version_id,
                'rating' => $validated['rating'],
                'reason' => $validated['rating'] === 'not_helpful' ? ($validated['reason'] ?? null) : null,
                'comment' => $validated['rating'] === 'not_helpful'
                    ? (isset($validated['comment']) ? trim($validated['comment']) : null)
                    : null,
            ]
        );

        app(AuditLogService::class)->record(
            action: 'submit_faq_feedback',
            page: 'chatbot',
            targetType: 'faq',
            targetId: (int) $log->faq_id,
            faqId: (int) $log->faq_id,
            newValues: [
                'rating' => $feedback->rating,
                'reason' => $feedback->reason,
                'comment' => $feedback->comment,
                'faq_version_id' => $feedback->faq_version_id,
            ],
            description: 'Submitted FAQ feedback for FAQ #' . $log->faq_id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Thank you for helping us improve our FAQ answers.',
            'rating' => $feedback->rating,
        ]);
    }

    /**
     * Return current feedback plus immutable response-version history.
     *
     * `version_id` may be supplied by the admin UI to inspect an older
     * response generation. No mutation endpoint exists for versions or
     * historical feedback.
     */
    public function forFaq(Request $request, Faq $faq)
    {
        $validated = $request->validate([
            'rating' => ['nullable', Rule::in(['all', 'helpful', 'not_helpful'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'version_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $versions = FaqVersion::query()
            ->where('faq_id', $faq->id)
            ->with('changedBy:id,first_name,last_name')
            ->withCount([
                'feedback as likes_count' => fn ($query) =>
                    $query->where('rating', 'helpful'),
                'feedback as dislikes_count' => fn ($query) =>
                    $query->where('rating', 'not_helpful'),
            ])
            ->orderByDesc('version_number')
            ->get();

        $currentVersion = $faq->currentVersion;

        abort_unless($currentVersion, 404, 'This FAQ does not have a response version.');

        $selectedVersion = $currentVersion;

        if (!empty($validated['version_id'])) {
            $selectedVersion = $versions->firstWhere('id', (int) $validated['version_id']);
            abort_unless($selectedVersion, 404, 'The requested FAQ response version does not exist.');
        }

        $total = (int) $selectedVersion->feedback()->count();
        $likes = (int) $selectedVersion->feedback()
            ->where('rating', 'helpful')
            ->count();
        $dislikes = (int) $selectedVersion->feedback()
            ->where('rating', 'not_helpful')
            ->count();

        $minimumRatings = (int) config('faq_feedback.minimum_ratings_for_review', 5);
        $priorityMinimum = (int) config('faq_feedback.priority_minimum_ratings', 10);
        $negativeRate = $total > 0 ? $dislikes / $total : 0;
        $needsReview = $total >= $minimumRatings && $dislikes > $likes;
        $priorityReview = $total >= $priorityMinimum
            && $negativeRate >= (float) config('faq_feedback.priority_negative_rate', 0.60);

        $rating = $validated['rating'] ?? 'all';
        $feedbackQuery = FaqFeedback::query()
            ->where('faq_version_id', $selectedVersion->id)
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
            ],
            'current_version_id' => $currentVersion->id,
            'version' => [
                'id' => $selectedVersion->id,
                'version_number' => (int) $selectedVersion->version_number,
                'is_current' => (int) $selectedVersion->id === (int) $currentVersion->id,
                'question' => $selectedVersion->question,
                'answer' => $selectedVersion->answer,
                'question_fil' => $selectedVersion->question_fil,
                'answer_fil' => $selectedVersion->answer_fil,
                'published_at' => $selectedVersion->created_at?->format('M j, Y · g:i A'),
                'superseded_at' => $selectedVersion->superseded_at?->format('M j, Y · g:i A'),
                'changed_by' => $selectedVersion->changedBy
                    ? trim($selectedVersion->changedBy->first_name . ' ' . $selectedVersion->changedBy->last_name)
                    : null,
            ],
            'versions' => $versions->map(fn (FaqVersion $version) => [
                'id' => $version->id,
                'version_number' => (int) $version->version_number,
                'is_current' => (int) $version->id === (int) $currentVersion->id,
                'published_at' => $version->created_at?->format('M j, Y · g:i A'),
                'superseded_at' => $version->superseded_at?->format('M j, Y · g:i A'),
                'changed_by' => $version->changedBy
                    ? trim($version->changedBy->first_name . ' ' . $version->changedBy->last_name)
                    : null,
                'likes' => (int) $version->likes_count,
                'dislikes' => (int) $version->dislikes_count,
                'total' => (int) $version->likes_count + (int) $version->dislikes_count,
                'response_preview' => trim((string) $version->answer),
            ])->values(),
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
            'stats' => [
                'likes' => $likes,
                'dislikes' => $dislikes,
                'total' => $total,
                'negative_rate' => round($negativeRate * 100, 1),
                'needs_review' => $needsReview,
                'priority_review' => $priorityReview,
                'minimum_ratings' => $minimumRatings,
            ],
        ]);
    }
}
