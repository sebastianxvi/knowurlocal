<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\AgencyEvaluation;
use App\Models\AgencyEvaluationModerationLog;
use App\Models\AgencyEvaluationReport;
use App\Models\AgencyEvaluationResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\AgencyEvaluationNotificationService;

class AgencyEvaluationManagementController extends Controller
{
    private function mayModerate($user): bool
    {
        return $user && ($user->role === 'superadmin'
            || ($user->role === 'admin' && (bool) $user->can_moderate_evaluations));
    }

    private function mayRespond($user, Agency $agency): bool
    {
        if (!$user) return false;

        if ($user->role === 'agency_admin') {
            return (int) $user->agency_id === (int) $agency->id;
        }

        if (!in_array($user->role, ['admin', 'superadmin'], true)) return false;

        // Central administrators cover only agencies without an active assigned
        // Agency Admin. They never take over an agency-admin-managed response.
        return !$agency->administrators()->where('status', 'active')->exists();
    }

    public function publicReport(Request $request, Agency $agency, AgencyEvaluation $evaluation)
    {
        abort_unless($request->user()->role === 'user', 403);
        abort_unless((int) $evaluation->agency_id === (int) $agency->id, 404);
        abort_unless($evaluation->status === 'published', 404);
        abort_if((int) $evaluation->user_id === (int) $request->user()->id, 422, 'You cannot report your own evaluation.');

        $validated = $request->validate([
            'reason' => ['required', Rule::in(['spam', 'offensive', 'personal_information', 'harassment', 'irrelevant', 'other'])],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $report = AgencyEvaluationReport::firstOrCreate(
            ['agency_evaluation_id' => $evaluation->id, 'reporter_id' => $request->user()->id],
            ['reason' => $validated['reason'], 'details' => trim((string) ($validated['details'] ?? '')) ?: null, 'status' => 'pending']
        );

        if (!$report->wasRecentlyCreated && $report->status === 'pending') {
            $report->update([
                'reason' => $validated['reason'],
                'details' => trim((string) ($validated['details'] ?? '')) ?: null,
            ]);
        }

        return response()->json([
            'message' => $report->wasRecentlyCreated ? 'Thank you. Your report has been submitted for review.' : 'You have already reported this evaluation.',
        ], $report->wasRecentlyCreated ? 201 : 200);
    }

    public function agencyIndex(Request $request)
    {
        $agency = $request->user()->agency;
        abort_unless($agency && !$agency->trashed(), 403);

        $base = $agency->evaluations()->where('status', 'published');
        $stats = (clone $base)->selectRaw('COUNT(*) total, COALESCE(AVG(rating),0) average')->first();
        $distribution = (clone $base)->selectRaw('rating, COUNT(*) total')->groupBy('rating')->pluck('total', 'rating');

        $unanswered = (clone $base)->whereDoesntHave('response')->count();
        $responded = (clone $base)->whereHas('response')->count();

        $filter = $request->query('filter', 'all');
        if (!in_array($filter, ['all', 'unanswered', 'responded'], true)) {
            $filter = 'all';
        }

        $evaluationQuery = (clone $base)->with(['response', 'user:id,first_name,last_name,profile_picture'])
            ->withCount(['reports', 'helpfulVotes'])
            ->when($filter === 'unanswered', fn ($query) => $query->whereDoesntHave('response'))
            ->when($filter === 'responded', fn ($query) => $query->whereHas('response'))
            ->latest();

        $evaluations = $evaluationQuery->paginate(25)->withQueryString();
        $evaluations->getCollection()->each(function (AgencyEvaluation $evaluation) {
            $identityVisible = !$evaluation->keep_identity_hidden;
            $evaluation->setAttribute('reviewer_name', $identityVisible ? $this->reviewerName($evaluation->user) : 'Anonymous User');
            $evaluation->setAttribute('reviewer_avatar_url', $identityVisible && $evaluation->user?->profile_picture
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($evaluation->user->profile_picture)
                : null);
            $evaluation->setAttribute('reviewer_is_anonymous', !$identityVisible);
        });

        return view('agency-admin.evaluations', compact(
            'agency', 'stats', 'distribution', 'evaluations',
            'unanswered', 'responded', 'filter'
        ));
    }

    public function adminOverview(Request $request)
    {
        $search = preg_replace('/\\s+/u', ' ', trim((string) $request->query('search', '')));

        $agencyQuery = Agency::query()->whereNull('deleted_at');

        $ratingFilter = (string) $request->query('rating_filter', 'all');
        if (!in_array($ratingFilter, ['all', 'below_3', 'three_to_four', 'four_plus', 'no_evaluations'], true)) {
            $ratingFilter = 'all';
        }

        // Filter by the agency's average published rating, not by whether it
        // has any individual review at that score. Agencies with no published
        // evaluations remain a separate category rather than being treated as 0.
        $averageRatingSql = '(SELECT AVG(agency_evaluations.rating)
            FROM agency_evaluations
            WHERE agency_evaluations.agency_id = agencies.id
              AND agency_evaluations.status = ?)';

        if ($ratingFilter === 'below_3') {
            $agencyQuery->whereRaw($averageRatingSql . ' < ?', ['published', 3]);
        } elseif ($ratingFilter === 'three_to_four') {
            $agencyQuery->whereRaw($averageRatingSql . ' >= ? AND ' . $averageRatingSql . ' < ?', ['published', 3, 'published', 4]);
        } elseif ($ratingFilter === 'four_plus') {
            $agencyQuery->whereRaw($averageRatingSql . ' >= ?', ['published', 4]);
        } elseif ($ratingFilter === 'no_evaluations') {
            $agencyQuery->whereDoesntHave('evaluations', fn ($query) => $query->where('status', 'published'));
        }

        // Match the same case-insensitive agency name/abbreviation behavior
        // used by NGA & NGO Management (including PostgreSQL deployments).
        if ($search !== '') {
            $searchTerm = '%' . mb_strtolower($search, 'UTF-8') . '%';
            $agencyQuery->where(function ($query) use ($searchTerm) {
                $query->whereRaw('LOWER(agency_name) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(agency_abbreviation) LIKE ?', [$searchTerm]);
            });
        }

        $agencies = $agencyQuery
            ->withCount(['evaluations as evaluation_count' => fn ($q) => $q->where('status', 'published')])
            ->withCount(['evaluations as unanswered_count' => fn ($q) => $q->where('status', 'published')->whereDoesntHave('response')])
            ->with(['administrators' => fn ($q) => $q->where('status', 'active')->select('id', 'agency_id', 'first_name', 'last_name')])
            ->withAvg(['evaluations as average_rating' => fn ($q) => $q->where('status', 'published')], 'rating')
            ->orderBy('agency_name')->paginate(25)->withQueryString();

        $summary = AgencyEvaluation::query()->where('status', 'published')
            ->selectRaw('COUNT(*) total, COALESCE(AVG(rating),0) average')->first();
        $pendingReports = AgencyEvaluationReport::where('status', 'pending')->count();
        $unanswered = AgencyEvaluation::where('status', 'published')->whereDoesntHave('response')->count();

        return view('admin.evaluations.overview', compact(
            'agencies', 'summary', 'pendingReports', 'unanswered', 'search', 'ratingFilter'
        ));
    }

    public function adminAgency(Agency $agency)
    {
        abort_if($agency->trashed(), 404);
        $base = $agency->evaluations()->where('status', 'published');
        $stats = (clone $base)->selectRaw('COUNT(*) total, COALESCE(AVG(rating),0) average')->first();
        $distribution = (clone $base)->selectRaw('rating, COUNT(*) total')->groupBy('rating')->pluck('total', 'rating');
        $evaluations = (clone $base)->with(['response', 'user:id,first_name,last_name,profile_picture'])
            ->withCount(['reports', 'helpfulVotes'])->latest()->paginate(50)->withQueryString();
        $evaluations->getCollection()->each(function (AgencyEvaluation $evaluation) {
            $identityVisible = !$evaluation->keep_identity_hidden;
            $evaluation->setAttribute('reviewer_name', $identityVisible ? $this->reviewerName($evaluation->user) : 'Anonymous User');
            $evaluation->setAttribute('reviewer_avatar_url', $identityVisible && $evaluation->user?->profile_picture
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($evaluation->user->profile_picture)
                : null);
            $evaluation->setAttribute('reviewer_is_anonymous', !$identityVisible);
        });
        $canRespond = $this->mayRespond(auth()->user(), $agency);
        return view('admin.evaluations.agency', compact('agency', 'stats', 'distribution', 'evaluations', 'canRespond'));
    }


    public function agencyAdminRespond(Request $request, AgencyEvaluation $evaluation)
    {
        $agency = $request->user()->agency;
        abort_unless($agency && (int) $evaluation->agency_id === (int) $agency->id, 404);
        return $this->respond($request, $agency, $evaluation);
    }

    public function respond(Request $request, Agency $agency, AgencyEvaluation $evaluation)
    {
        abort_unless((int) $evaluation->agency_id === (int) $agency->id, 404);
        abort_unless($evaluation->status === 'published', 404);
        abort_unless($this->mayRespond($request->user(), $agency), 403, 'You are not authorized to respond to this agency evaluation.');

        $validated = $request->validate(['body' => ['required', 'string', 'min:3', 'max:2000']]);
        $responseWasCreated = false;
        $response = DB::transaction(function () use ($request, $evaluation, $validated, &$responseWasCreated) {
            $existing = AgencyEvaluationResponse::where('agency_evaluation_id', $evaluation->id)->lockForUpdate()->first();
            if ($existing) {
                // Response authority follows the agency's current assignment.
                // When an agency administrator is assigned or becomes inactive,
                // the newly authorized responder may take over the single official
                // response without creating a competing public thread.
                $existing->update([
                    'body' => trim($validated['body']),
                    'responder_id' => $request->user()->id,
                    'responder_role' => $request->user()->role,
                ]);
                return $existing;
            }

            $responseWasCreated = true;
            return AgencyEvaluationResponse::create([
                'agency_evaluation_id' => $evaluation->id,
                'responder_id' => $request->user()->id,
                'responder_role' => $request->user()->role,
                'body' => trim($validated['body']),
            ]);
        });

        // Only the first official response should trigger the "agency responded"
        // notification. Editing/reassigning an existing response is not a new reply.
        if ($responseWasCreated) {
            app(AgencyEvaluationNotificationService::class)->officialResponse(
                $evaluation->fresh(['agency']),
                $response
            );
        }

        return back()->with('success', 'Official response saved.');
    }

    public function deleteResponse(Request $request, Agency $agency, AgencyEvaluationResponse $response)
    {
        $evaluation = $response->evaluation;
        abort_unless((int) $evaluation->agency_id === (int) $agency->id, 404);
        abort_unless($this->mayRespond($request->user(), $agency), 403);
        // Current authorized response owners may remove the single official
        // response; this also supports an assignment change without orphaning it.
        $response->delete();
        return back()->with('success', 'Official response removed.');
    }

    public function reports(Request $request)
    {
        abort_unless($this->mayModerate(auth()->user()), 403);

        $statusFilter = (string) $request->query('status', 'pending');
        if (!in_array($statusFilter, ['pending', 'resolved', 'all'], true)) {
            $statusFilter = 'pending';
        }

        $search = preg_replace('/\s+/u', ' ', trim((string) $request->query('search', '')));

        $reportedEvaluations = AgencyEvaluation::withTrashed()
            ->whereHas('reports')
            ->when($statusFilter === 'pending', function ($query) {
                // Hidden evaluations remain active moderation work even if older
                // report records were marked resolved by a previous workflow.
                $query->where(function ($queue) {
                    $queue->whereHas('reports', fn ($reports) => $reports->where('status', 'pending'))
                        ->orWhere('status', 'hidden');
                });
            })
            ->when($statusFilter === 'resolved', function ($query) {
                // A temporarily hidden evaluation is never considered resolved.
                $query->where('status', '!=', 'hidden')
                    ->whereHas('reports')
                    ->whereDoesntHave('reports', fn ($reports) => $reports->where('status', 'pending'));
            })
            ->when($search !== '', function ($query) use ($search) {
                $term = '%' . mb_strtolower($search, 'UTF-8') . '%';
                $query->where(function ($matches) use ($term) {
                    $matches->whereHas('agency', fn ($agency) => $agency->whereRaw('LOWER(agency_name) LIKE ?', [$term]))
                        ->orWhereRaw('LOWER(COALESCE(comment, \'\')) LIKE ?', [$term])
                        ->orWhereHas('reports', function ($reports) use ($term) {
                            $reports->whereRaw('LOWER(reason) LIKE ?', [$term])
                                ->orWhereRaw('LOWER(COALESCE(details, \'\')) LIKE ?', [$term]);
                        });
                });
            })
            ->with(['agency:id,agency_name'])
            ->withCount([
                'reports',
                'reports as pending_reports_count' => fn ($query) => $query->where(function ($reports) {
                    $reports->where('status', 'pending')
                        ->orWhereHas('evaluation', fn ($evaluation) => $evaluation->where('status', 'hidden'));
                }),
                'reports as resolved_reports_count' => fn ($query) => $query->whereIn('status', ['dismissed', 'actioned']),
            ])
            ->withMax('reports', 'created_at')
            ->orderByDesc('reports_max_created_at')
            ->paginate(15)
            ->withQueryString();

        $pendingCount = AgencyEvaluationReport::where(function ($query) {
            $query->where('status', 'pending')
                ->orWhereHas('evaluation', fn ($evaluation) => $evaluation->withTrashed()->where('status', 'hidden'));
        })->count();
        $resolvedCount = AgencyEvaluationReport::whereIn('status', ['dismissed', 'actioned'])
            ->whereHas('evaluation', fn ($query) => $query->withTrashed()->where('status', '!=', 'hidden'))
            ->count();
        $reportedEvaluationCount = AgencyEvaluation::withTrashed()->whereHas('reports')->count();
        $hiddenCount = AgencyEvaluation::where('status', 'hidden')->whereHas('reports')->count();

        return view('admin.evaluations.moderation', compact(
            'reportedEvaluations',
            'pendingCount',
            'resolvedCount',
            'reportedEvaluationCount',
            'hiddenCount',
            'statusFilter',
            'search'
        ));
    }

    public function reportsForEvaluation(Request $request, int $evaluation)
    {
        abort_unless($this->mayModerate($request->user()), 403);
        $evaluation = AgencyEvaluation::withTrashed()->findOrFail($evaluation);

        $reports = AgencyEvaluationReport::query()
            ->where('agency_evaluation_id', $evaluation->id)
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $reports->getCollection()->map(fn (AgencyEvaluationReport $report) => [
                'id' => $report->id,
                'reason' => \Illuminate\Support\Str::headline($report->reason),
                'details' => $report->details,
                'status' => $evaluation->status === 'hidden' ? 'pending' : $report->status,
                'created_at' => $report->created_at?->format('M d, Y · g:i A'),
                'resolution_note' => $report->resolution_note,
            ])->values(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function moderate(Request $request, int $evaluation)
    {
        abort_unless($this->mayModerate($request->user()), 403);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['dismiss', 'hide', 'restore', 'delete'])],
            'reason' => ['required_if:action,delete', 'nullable', 'string', 'min:5', 'max:1000'],
        ]);

        $action = $validated['action'];
        [$evaluationRecord, $moderationLog] = DB::transaction(function () use ($request, $evaluation, $validated, $action) {
            $lockedEvaluation = AgencyEvaluation::withTrashed()
                ->whereKey($evaluation)
                ->lockForUpdate()
                ->firstOrFail();

            $pendingReports = AgencyEvaluationReport::where('agency_evaluation_id', $lockedEvaluation->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->get();
            $note = trim((string) ($validated['reason'] ?? '')) ?: null;
            if ($action === 'delete' && $note === null) {
                abort(422, 'Please provide a reason for removing this evaluation.');
            }

            if ($action === 'dismiss') {
                if ($lockedEvaluation->trashed() || $lockedEvaluation->status !== 'published' || $pendingReports->isEmpty()) {
                    abort(422, 'Only a published evaluation with pending reports can have its reports dismissed.');
                }
                AgencyEvaluationReport::whereIn('id', $pendingReports->pluck('id'))->update([
                    'status' => 'dismissed', 'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(), 'resolution_note' => $note, 'updated_at' => now(),
                ]);
            } elseif ($action === 'hide') {
                if ($lockedEvaluation->trashed() || $lockedEvaluation->status !== 'published' || $pendingReports->isEmpty()) {
                    abort(422, 'Only a published evaluation with pending reports can be temporarily hidden.');
                }
                // Hiding is an interim safeguard, not a report resolution.
                $lockedEvaluation->update(['status' => 'hidden']);
            } elseif ($action === 'restore') {
                if ($lockedEvaluation->trashed() || $lockedEvaluation->status !== 'hidden') {
                    abort(422, 'Only a temporarily hidden evaluation can be restored.');
                }
                $lockedEvaluation->update(['status' => 'published']);
                if ($pendingReports->isNotEmpty()) {
                    AgencyEvaluationReport::whereIn('id', $pendingReports->pluck('id'))->update([
                        'status' => 'dismissed', 'reviewed_by' => $request->user()->id,
                        'reviewed_at' => now(), 'resolution_note' => $note, 'updated_at' => now(),
                    ]);
                }
            } elseif ($action === 'delete') {
                if ($lockedEvaluation->trashed() || $lockedEvaluation->status !== 'hidden') {
                    abort(422, 'Temporarily hide an evaluation before removing it.');
                }
                $lockedEvaluation->update(['status' => 'removed']);
                if ($pendingReports->isNotEmpty()) {
                    AgencyEvaluationReport::whereIn('id', $pendingReports->pluck('id'))->update([
                        'status' => 'actioned', 'reviewed_by' => $request->user()->id,
                        'reviewed_at' => now(), 'resolution_note' => $note, 'updated_at' => now(),
                    ]);
                }
                // Soft delete: public queries stop seeing this record, while its
                // original content, reports, responses and audit trail remain intact.
                $lockedEvaluation->delete();
            }

            $moderationLog = AgencyEvaluationModerationLog::create([
                'agency_evaluation_id' => $lockedEvaluation->id,
                'admin_id' => $request->user()->id,
                'actor_label' => trim($request->user()->first_name . ' ' . $request->user()->last_name) ?: 'Administrator',
                'action' => $action,
                'reason' => $note,
                'metadata' => [
                    'report_count' => $pendingReports->count(),
                    'report_ids' => $pendingReports->pluck('id')->values()->all(),
                    'evaluation_status' => $lockedEvaluation->fresh()->status,
                    'evaluation_deleted' => $lockedEvaluation->trashed(),
                ],
            ]);

            return [$lockedEvaluation->fresh()->load('agency'), $moderationLog];
        });

        if (in_array($action, ['hide', 'restore', 'delete'], true)) {
            app(AgencyEvaluationNotificationService::class)->moderationDecision($evaluationRecord, $moderationLog, $action);
        }

        return back()->with('success', match ($action) {
            'dismiss' => 'The pending reports have been dismissed. The evaluation remains publicly visible.',
            'hide' => 'The evaluation is temporarily hidden. Its reports remain pending until a final decision is made.',
            'restore' => 'The evaluation has been restored and its pending reports have been dismissed.',
            'delete' => 'The evaluation has been removed from public view. Its records are retained for moderation history, and the author may submit a new evaluation.',
        });
    }

    /**
     * Return a reviewer's full name only when their evaluation explicitly
     * allows identity disclosure. Anonymous labels are handled by the caller.
     */
    private function reviewerName($user): string
    {
        if (!$user) {
            return 'Public User';
        }

        $name = trim(implode(' ', array_filter([
            trim((string) $user->first_name),
            trim((string) $user->last_name),
        ])));

        return $name !== '' ? $name : 'Public User';
    }
}
