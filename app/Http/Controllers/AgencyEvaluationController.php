<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\AgencyEvaluation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgencyEvaluationController extends Controller
{
    public function index(Request $request, Agency $agency): JsonResponse
    {
        $published = $agency->evaluations()->where('status', 'published');
        $summary = (clone $published)->selectRaw('COUNT(*) as total, COALESCE(AVG(rating), 0) as average')->first();
        $selectedRating = $request->query('rating');
        if ($selectedRating !== null && $selectedRating !== '') {
            $validatedFilter = validator(['rating' => $selectedRating], [
                'rating' => ['required', 'integer', 'between:1,5'],
            ])->validate();
            $selectedRating = (int) $validatedFilter['rating'];
        } else {
            $selectedRating = null;
        }
        $distribution = (clone $published)
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $filteredReviews = clone $published;
        if ($selectedRating !== null) {
            $filteredReviews->where('rating', $selectedRating);
        }

        $filteredTotal = (clone $filteredReviews)->count();
        $page = (int) validator(
            ['page' => $request->query('page', 1)],
            ['page' => ['required', 'integer', 'min:1', 'max:100000']]
        )->validate()['page'];
        $perPage = 100;

        // Pin the signed-in user's own evaluation at the beginning of the
        // result set, then show all other matching evaluations newest-first.
        // Offset pagination is bounded to 100 rows per request.
        $reviewRecords = (clone $filteredReviews)
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$request->user()->id])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->with(['user:id,first_name,last_name', 'response:id,agency_evaluation_id,responder_role,body,updated_at'])
            ->get(['id', 'user_id', 'rating', 'comment', 'created_at']);

        $reviewIds = $reviewRecords->pluck('id');
        $helpfulCounts = DB::table('agency_evaluation_helpful_votes')
            ->select('agency_evaluation_id', DB::raw('COUNT(*) as total'))
            ->whereIn('agency_evaluation_id', $reviewIds)
            ->groupBy('agency_evaluation_id')
            ->pluck('total', 'agency_evaluation_id');
        $myHelpfulVotes = DB::table('agency_evaluation_helpful_votes')
            ->where('user_id', $request->user()->id)
            ->whereIn('agency_evaluation_id', $reviewIds)
            ->pluck('agency_evaluation_id')
            ->flip();

        $reviews = $reviewRecords
            ->sortByDesc('created_at')
            ->map(fn (AgencyEvaluation $evaluation) => [
                'id' => $evaluation->id,
                'rating' => $evaluation->rating,
                'comment' => $evaluation->comment,
                'created_at' => $evaluation->created_at?->toISOString(),
                'reviewer' => $this->maskedReviewerName($evaluation->user),
                'is_mine' => (int) $evaluation->user_id === (int) $request->user()->id,
                'helpful_count' => (int) ($helpfulCounts[$evaluation->id] ?? 0),
                'has_marked_helpful' => $myHelpfulVotes->has($evaluation->id),
                'response' => $evaluation->response ? [
                    'body' => $evaluation->response->body,
                    'responder_role' => $evaluation->response->responder_role,
                    'updated_at' => $evaluation->response->updated_at?->toISOString(),
                ] : null,
            ])->values();

        $ownEvaluation = null;
        if ($request->user()->role === 'user') {
            $ownEvaluation = $agency->evaluations()
                ->where('user_id', $request->user()->id)
                ->first(['id', 'rating', 'comment', 'status', 'updated_at']);
        }

        return response()->json([
            'average' => round((float) $summary->average, 1),
            'total' => (int) $summary->total,
            'distribution' => collect(range(1, 5))->mapWithKeys(fn ($rating) => [
                $rating => (int) ($distribution[$rating] ?? 0),
            ]),
            'active_filter' => $selectedRating,
            'filtered_total' => (int) $filteredTotal,
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => ($page * $perPage) < $filteredTotal,
            'reviews' => $reviews,
            'my_evaluation' => $ownEvaluation ? [
                'rating' => $ownEvaluation->rating,
                'comment' => $ownEvaluation->comment,
                'updated_at' => $ownEvaluation->updated_at?->toISOString(),
                'status' => $ownEvaluation->status,
            ] : null,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, Agency $agency): JsonResponse
    {
        abort_unless($request->user()->role === 'user', 403, 'Only public users can submit agency evaluations.');

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1500'],
            'confirmed_experience' => ['required', 'accepted'],
        ]);

        $evaluation = DB::transaction(function () use ($request, $agency, $validated) {
            // Serialize submissions per agency after allowing multiple historical (soft-deleted) rows.
            Agency::query()->whereKey($agency->id)->lockForUpdate()->firstOrFail();

            $comment = isset($validated['comment']) ? trim($validated['comment']) ?: null : null;
            $evaluation = AgencyEvaluation::query()
                ->where('agency_id', $agency->id)
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            if ($evaluation) {
                abort_unless($evaluation->status === 'published', 403, 'This evaluation is currently unavailable for editing.');

                $contentChanged = (int) $evaluation->rating !== (int) $validated['rating']
                    || (string) ($evaluation->comment ?? '') !== (string) ($comment ?? '');

                // Helpful votes refer to the content people actually evaluated.
                // Clear them only when the saved rating/comment materially changes;
                // opening and cancelling the editor (or saving unchanged content)
                // must not erase genuine feedback.
                if ($contentChanged) {
                    DB::table('agency_evaluation_helpful_votes')
                        ->where('agency_evaluation_id', $evaluation->id)
                        ->delete();
                }

                $evaluation->rating = $validated['rating'];
                $evaluation->comment = $comment;
                $evaluation->status = 'published';
                $evaluation->save();

                return $evaluation;
            }

            // The unique database constraint remains the final safeguard against
            // concurrent first-time submissions for the same user and agency.
            return AgencyEvaluation::create([
                'agency_id' => $agency->id,
                'user_id' => $request->user()->id,
                'rating' => $validated['rating'],
                'comment' => $comment,
                'status' => 'published',
            ]);
        });

        return response()->json([
            'message' => 'Your evaluation has been saved.',
            'evaluation' => [
                'rating' => $evaluation->rating,
                'comment' => $evaluation->comment,
                'updated_at' => $evaluation->updated_at?->toISOString(),
            ],
        ]);
    }

    public function destroy(Request $request, Agency $agency): JsonResponse
    {
        abort_unless($request->user()->role === 'user', 403, 'Only public users can delete their own agency evaluations.');

        $evaluation = $agency->evaluations()
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$evaluation) {
            return response()->json(['message' => 'Your evaluation could not be found.'], 404);
        }

        // A public author cannot bypass an active moderation review by deleting
        // the hidden record themselves and immediately submitting a replacement.
        abort_unless($evaluation->status === 'published', 403,
            'Your evaluation is temporarily hidden while it is being reviewed.');

        $evaluation->delete();

        return response()->json(['message' => 'Your evaluation has been deleted.']);
    }

    public function toggleHelpful(Request $request, Agency $agency, AgencyEvaluation $evaluation): JsonResponse
    {
        abort_unless($request->user()->role === 'user', 403, 'Only public users can mark evaluations as helpful.');
        abort_unless((int) $evaluation->agency_id === (int) $agency->id, 404);
        abort_if((int) $evaluation->user_id === (int) $request->user()->id, 422, 'You cannot mark your own evaluation as helpful.');
        abort_unless($evaluation->status === 'published', 404);

        $markedHelpful = DB::transaction(function () use ($request, $evaluation) {
            $vote = DB::table('agency_evaluation_helpful_votes')
                ->where('agency_evaluation_id', $evaluation->id)
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            if ($vote) {
                DB::table('agency_evaluation_helpful_votes')
                    ->where('id', $vote->id)
                    ->delete();
                return false;
            }

            DB::table('agency_evaluation_helpful_votes')->insertOrIgnore([
                'agency_evaluation_id' => $evaluation->id,
                'user_id' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return true;
        });

        $count = DB::table('agency_evaluation_helpful_votes')
            ->where('agency_evaluation_id', $evaluation->id)
            ->count();

        return response()->json([
            'marked_helpful' => $markedHelpful,
            'helpful_count' => $count,
        ])->header('Cache-Control', 'private, no-store');
    }

    private function maskedReviewerName($user): string
    {
        if (!$user) {
            return 'Public User';
        }

        $parts = array_filter([
            trim((string) $user->first_name),
            trim((string) $user->last_name),
        ]);

        if (!$parts) {
            return 'Public User';
        }

        return collect($parts)->map(function ($part) {
            $firstCharacter = mb_substr($part, 0, 1);
            $remainingLength = max(2, min(5, mb_strlen($part) - 1));
            return $firstCharacter . str_repeat('*', $remainingLength);
        })->implode(' ');
    }
}
