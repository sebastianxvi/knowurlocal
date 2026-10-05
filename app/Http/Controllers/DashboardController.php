<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\ChatbotLog;
use App\Models\CollaborationTask;
use App\Models\Faq;
use App\Models\FaqFeedback;
use App\Models\SupportRequest;
use App\Models\User;
use App\Models\UserLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Render the operational dashboard.
     *
     * The dashboard intentionally receives only summary/workflow data.
     * Heavy analytics are loaded only on the dedicated Analytics page.
     */
    public function index()
    {
        return view('admin.dashboard', $this->dashboardData());
    }

    /**
     * Render the dedicated analytics workspace.
     */
    public function analytics()
    {
        return view('admin.analytics', $this->analyticsData('7d'));
    }

    /**
     * Export the brief dashboard summary.
     */
    public function exportDashboardPdf()
    {
        $pdf = Pdf::loadView('admin.reports.dashboard', $this->dashboardData());

        return $pdf->download(
            'KNOWURLOCAL_Dashboard_Summary_' . now()->format('Y-m-d') . '.pdf'
        );
    }

    /**
     * Export analytics for the exact period currently selected by the admin.
     */
    public function exportAnalyticsPdf()
    {
        $period = request()->query('period', '7d');
        $month = request()->query('month');
        $data = $this->analyticsData($period, $month);

        $pdf = Pdf::loadView('admin.reports.analytics', $data);

        return $pdf->download(
            'KNOWURLOCAL_Analytics_' . ($data['period'] === 'month' ? $data['selectedMonth'] : $data['period']) . '_' . now()->format('Y-m-d') . '.pdf'
        );
    }

    /**
     * Export the full report, combining the concise dashboard summary with
     * the selected analytics period.
     */
    public function exportFullPdf()
    {
        $analytics = $this->analyticsData(
            request()->query('period', '7d'),
            request()->query('month')
        );

        $data = array_merge($this->dashboardData(), $analytics);
        $pdf = Pdf::loadView('admin.reports.full', $data);

        return $pdf->download(
            'KNOWURLOCAL_Full_Report_' . now()->format('Y-m-d') . '.pdf'
        );
    }

    /**
     * Backwards-compatible alias for older links/bookmarks.
     */
    public function exportPdf()
    {
        return $this->exportDashboardPdf();
    }

    /**
     * Build lightweight dashboard data.
     *
     * Important performance rule:
     * dashboard summary cards use COUNT/EXISTS queries instead of
     * loading full tables into PHP.
     */
    private function dashboardData(bool $includeReportActivity = false): array
    {
        $totalAgencies = Agency::count();

        $agencyTypeCounts = Agency::query()
            ->join('agency_types', 'agency_types.id', '=', 'agencies.agency_type_id')
            ->select('agency_types.name')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('agency_types.name')
            ->pluck('total', 'name');

        $totalNGA = (int) ($agencyTypeCounts['NGA'] ?? 0);
        $totalNGO = (int) ($agencyTypeCounts['NGO'] ?? 0);

        $totalFaqs = Faq::count();

        $faqFeedbackSummary = $this->faqFeedbackSummary();
        $faqFeedbackHelpful = $faqFeedbackSummary['helpful'];
        $faqFeedbackNotHelpful = $faqFeedbackSummary['not_helpful'];
        $faqFeedbackNeedsReview = $faqFeedbackSummary['needs_review'];
        $faqFeedbackReviewedOpen = $faqFeedbackSummary['reviewed_open'];
        $faqFeedbackOutstanding = $faqFeedbackNeedsReview + $faqFeedbackReviewedOpen;
        $faqFeedbackResolved = $faqFeedbackSummary['resolved'];
        $faqFeedbackTotal = $faqFeedbackSummary['total'];

        /*
         * Contributor ranking is report-only data. Avoid the extra grouped
         * queries on the normal dashboard request.
         */
        $topFaqCount = 0;
        $topFaqContributorTieCount = 0;
        $topFaqContributors = collect();

        if ($includeReportActivity) {
            $faqCountsByAgency = Faq::query()
                ->whereNotNull('agency_id')
                ->whereHas('agency')
                ->select('agency_id')
                ->selectRaw('COUNT(*) AS faq_count')
                ->groupBy('agency_id')
                ->orderByDesc('faq_count')
                ->get();

            $topFaqCount = (int) ($faqCountsByAgency->first()?->faq_count ?? 0);

            $topFaqAgencyIds = $topFaqCount > 0
                ? $faqCountsByAgency
                    ->where('faq_count', $topFaqCount)
                    ->pluck('agency_id')
                : collect();

            $topFaqContributorTieCount = $topFaqAgencyIds->count();

            $topFaqContributors = $topFaqContributorTieCount > 0
                ? Agency::query()
                    ->whereIn('id', $topFaqAgencyIds)
                    ->orderBy('agency_abbreviation')
                    ->pluck('agency_abbreviation')
                : collect();
        }

        $totalUsers = User::where('role', 'user')->count();

        $totalInquiries = SupportRequest::count();

        $pendingInquiries = SupportRequest::where(
            'status',
            'pending'
        )->count();

        $answeredInquiries = SupportRequest::where(
            'status',
            'answered'
        )->count();

        $incompleteAgencies = Agency::query()
            ->where(function ($query) {
                $query
                    ->whereNull('agency_location')
                    ->orWhere('agency_location', '')
                    ->orWhereNull('agency_description')
                    ->orWhere('agency_description', '')
                    ->orWhereNull('services_offered')
                    ->orWhere('services_offered', '')
                    ->orWhereNull('office_hours')
                    ->orWhere('office_hours', '')
                    ->orWhereNull('lat')
                    ->orWhereNull('lng')
                    ->orWhereNull('agency_type_id')
                    ->orWhereNull('category_id');
            })
            ->count();

        $completeAgencies = max(
            0,
            $totalAgencies - $incompleteAgencies
        );

        $incompleteFaqs = Faq::query()
            ->where(function ($query) {
                $query
                    ->whereNull('agency_id')
                    ->orWhereNull('question')
                    ->orWhere('question', '')
                    ->orWhereNull('answer')
                    ->orWhere('answer', '')
                    ->orWhereNull('question_fil')
                    ->orWhere('question_fil', '')
                    ->orWhereNull('answer_fil')
                    ->orWhere('answer_fil', '');
            })
            ->count();

        $completeFaqs = max(
            0,
            $totalFaqs - $incompleteFaqs
        );

        $totalNeedsAttention =
            $pendingInquiries +
            $incompleteAgencies +
            $incompleteFaqs +
            $faqFeedbackOutstanding;

        /*
         * Collaboration is based on explicit work handoffs/review requests,
         * not audit-log volume. A task only appears here when one administrator
         * has actually asked another administrator to do something.
         */
        $collaborationQuery = CollaborationTask::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->with(['creator', 'assignee', 'target'])
            ->orderByRaw("CASE WHEN due_at IS NOT NULL AND due_at < ? THEN 0 ELSE 1 END", [now()])
            ->orderByRaw("CASE WHEN status = 'in_progress' THEN 0 ELSE 1 END")
            ->latest('created_at');

        if (auth()->user()->role === 'admin') {
            $collaborationQuery->where(function ($query) {
                $query
                    ->where('assigned_to_id', auth()->id())
                    ->orWhere('created_by_id', auth()->id());
            });
        }

        $collaborationOpenCount = (clone $collaborationQuery)->count();

        $collaborationYourActionCount = CollaborationTask::query()
            ->where('assigned_to_id', auth()->id())
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        $collaborationReviewCount = CollaborationTask::query()
            ->where('assigned_to_id', auth()->id())
            ->where('task_type', 'review')
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        $collaborationHandoffCount = (clone $collaborationQuery)
            ->where('task_type', 'handoff')
            ->count();

        $collaborationTasks = (clone $collaborationQuery)
            ->limit(6)
            ->get();

        /*
         * The full activity feed is useful in the PDF report but is not
         * rendered on the operational dashboard.
         */
        $recentActivity = $includeReportActivity
            ? $this->authorizedActivityQuery()
                ->latest()
                ->limit(8)
                ->get()
            : collect();

        return compact(
            'totalAgencies',
            'totalNGA',
            'totalNGO',
            'totalFaqs',
            'faqFeedbackHelpful',
            'faqFeedbackNotHelpful',
            'faqFeedbackNeedsReview',
            'faqFeedbackReviewedOpen',
            'faqFeedbackOutstanding',
            'faqFeedbackResolved',
            'faqFeedbackTotal',
            'topFaqCount',
            'topFaqContributorTieCount',
            'topFaqContributors',
            'totalUsers',
            'totalInquiries',
            'pendingInquiries',
            'answeredInquiries',
            'incompleteAgencies',
            'completeAgencies',
            'incompleteFaqs',
            'completeFaqs',
            'totalNeedsAttention',
            'collaborationOpenCount',
            'collaborationYourActionCount',
            'collaborationReviewCount',
            'collaborationHandoffCount',
            'collaborationTasks',
            'recentActivity'
        );
    }

    /**
     * Build only the data required by the analytics page/report.
     *
     * Trend counts are aggregated by the database rather than loading
     * every request/log row into application memory.
     */
    private function analyticsData(?string $requestedPeriod = null, ?string $requestedMonth = null): array
    {
        $totalInquiries = SupportRequest::count();

        $statusCounts = SupportRequest::query()
            ->select('status')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($value) => (int) $value);

        $queueStatusCounts = [
            'pending' => (int) ($statusCounts['pending'] ?? 0),
            'awaiting_confirmation' => (int) ($statusCounts['awaiting_confirmation'] ?? 0),
            'needs_follow_up' => (int) ($statusCounts['needs_follow_up'] ?? 0),
            'answered' => (int) ($statusCounts['answered'] ?? 0),
        ];

        $pendingInquiries = (int) ($statusCounts['pending'] ?? 0);
        $awaitingConfirmation = (int) ($statusCounts['awaiting_confirmation'] ?? 0);
        $needsFollowUp = (int) ($statusCounts['needs_follow_up'] ?? 0);
        $answeredInquiries = (int) ($statusCounts['answered'] ?? 0);
        $openInquiries = $pendingInquiries + $awaitingConfirmation + $needsFollowUp;

        $responseRate = $totalInquiries > 0
            ? round(($answeredInquiries / $totalInquiries) * 100)
            : 0;

        $hasAnswerSeenAt = Schema::hasColumn('support_requests', 'answer_seen_at');

        $seenAnswers = $hasAnswerSeenAt
            ? SupportRequest::query()
                ->where('status', 'answered')
                ->whereNotNull('answer_seen_at')
                ->count()
            : 0;

        $unseenAnswers = $hasAnswerSeenAt
            ? SupportRequest::query()
                ->where('status', 'answered')
                ->whereNull('answer_seen_at')
                ->count()
            : $answeredInquiries;

        $averageResponseMinutes = $this->averageResponseMinutes();
        $averageResponseTime = $this->formatMinutes($averageResponseMinutes);

        // The trend is user-selectable: a rolling 7-day window, a rolling
        // 30-day window, or a calendar month such as July 2026.
        $period = $requestedPeriod ?? request()->query('period', '7d');
        if (! in_array($period, ['7d', '30d', 'month'], true)) {
            $period = '7d';
        }

        $selectedMonth = $requestedMonth ?? request()->query('month');
        $periodLabel = '7 days';

        if ($period === 'month') {
            try {
                $monthDate = $selectedMonth
                    ? Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth()
                    : now()->startOfMonth();
            } catch (\Throwable $e) {
                $monthDate = now()->startOfMonth();
            }

            $start = $monthDate->copy()->startOfMonth();
            $end = $monthDate->copy()->endOfMonth();
            $selectedMonth = $start->format('Y-m');
            $periodLabel = $start->format('F Y');
        } elseif ($period === '30d') {
            $start = now()->startOfDay()->subDays(29);
            $end = now()->endOfDay();
            $periodLabel = '30 days';
        } else {
            $start = now()->startOfDay()->subDays(6);
            $end = now()->endOfDay();
        }

        $submittedByDay = SupportRequest::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) AS date')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $answeredByDay = SupportRequest::query()
            ->where('status', 'answered')
            ->whereNotNull('answered_at')
            ->whereBetween('answered_at', [$start, $end])
            ->selectRaw('DATE(answered_at) AS date')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $days = $start->diffInDays($end) + 1;
        $inquiryTrend = collect(range(0, $days - 1))
            ->map(function (int $day) use ($start, $submittedByDay, $answeredByDay) {
                $date = $start->copy()->addDays($day);
                $key = $date->format('Y-m-d');

                return [
                    'date' => $key,
                    'label' => $date->format('M j'),
                    'submitted' => (int) ($submittedByDay[$key] ?? 0),
                    'answered' => (int) ($answeredByDay[$key] ?? 0),
                ];
            })
            ->all();

        $trendSubmitted = collect($inquiryTrend)->sum('submitted');
        $trendAnswered = collect($inquiryTrend)->sum('answered');
        // Do not present answered/submitted as a percentage: answers can legitimately
        // exceed submissions in a period because administrators may clear a backlog.
        // The UI therefore reports the actual number answered in the selected period.
        $trendResponseRate = null;

        // Data-management health: useful for administrators maintaining the
        // public knowledge base, not just support operations.
        $totalAgencies = Agency::count();
        $incompleteAgencies = Agency::query()
            ->where(function ($query) {
                $query
                    ->whereNull('agency_location')->orWhere('agency_location', '')
                    ->orWhereNull('agency_description')->orWhere('agency_description', '')
                    ->orWhereNull('services_offered')->orWhere('services_offered', '')
                    ->orWhereNull('office_hours')->orWhere('office_hours', '')
                    ->orWhereNull('lat')->orWhereNull('lng')
                    ->orWhereNull('agency_type_id')->orWhereNull('category_id');
            })
            ->count();
        $completeAgencies = max(0, $totalAgencies - $incompleteAgencies);

        $totalFaqs = Faq::count();
        $faqFeedbackSummary = $this->faqFeedbackSummary();
        $faqFeedbackHelpful = $faqFeedbackSummary['helpful'];
        $faqFeedbackNotHelpful = $faqFeedbackSummary['not_helpful'];
        $faqFeedbackNeedsReview = $faqFeedbackSummary['needs_review'];
        $faqFeedbackReviewedOpen = $faqFeedbackSummary['reviewed_open'];
        $faqFeedbackOutstanding = $faqFeedbackNeedsReview + $faqFeedbackReviewedOpen;
        $faqFeedbackResolved = $faqFeedbackSummary['resolved'];
        $faqFeedbackTotal = $faqFeedbackSummary['total'];

        $incompleteFaqs = Faq::query()
            ->where(function ($query) {
                $query
                    ->whereNull('agency_id')
                    ->orWhereNull('question')->orWhere('question', '')
                    ->orWhereNull('answer')->orWhere('answer', '')
                    ->orWhereNull('question_fil')->orWhere('question_fil', '')
                    ->orWhereNull('answer_fil')->orWhere('answer_fil', '');
            })
            ->count();
        $completeFaqs = max(0, $totalFaqs - $incompleteFaqs);

        // Which agencies receive the most support requests? This is more
        // actionable than showing only chatbot agency popularity.
        $topSupportAgencies = SupportRequest::query()
            ->whereNotNull('agency_id')
            ->select('agency_id')
            ->selectRaw('COUNT(*) AS request_count')
            ->groupBy('agency_id')
            ->orderByDesc('request_count')
            ->limit(5)
            ->with('agency')
            ->get();

        /*
         * Chatbot / knowledge-base analytics. The feature remains compatible
         * with installations that have not yet applied the analytics columns.
         */
        $totalChatbotInteractions = ChatbotLog::count();
        $hasOutcome = Schema::hasColumn('chatbot_logs', 'outcome');
        $hasFaqId = Schema::hasColumn('chatbot_logs', 'faq_id');
        $hasMatchMethod = Schema::hasColumn('chatbot_logs', 'match_method');

        $knowledgeQuestions = 0;
        $faqAnswered = 0;
        $fallbackQuestions = 0;
        $clarificationQuestions = 0;
        $ruleMatches = 0;
        $semanticMatches = 0;
        $similarityMatches = 0;
        $popularFaqs = collect();
        $popularAgencies = collect();

        if ($hasOutcome) {
            $knowledgeQuestions = ChatbotLog::whereIn(
                'outcome',
                ['answered', 'fallback', 'clarification', 'wrong_agency']
            )->count();

            $fallbackQuestions = ChatbotLog::where('outcome', 'fallback')->count();
            $clarificationQuestions = ChatbotLog::where('outcome', 'clarification')->count();

            if ($hasFaqId) {
                $faqAnswered = ChatbotLog::query()
                    ->where('outcome', 'answered')
                    ->whereNotNull('faq_id')
                    ->count();

                $popularFaqs = ChatbotLog::query()
                    ->where('outcome', 'answered')
                    ->whereNotNull('faq_id')
                    ->select('faq_id')
                    ->selectRaw('COUNT(*) AS usage_count')
                    ->groupBy('faq_id')
                    ->orderByDesc('usage_count')
                    ->with('faq.agency')
                    ->limit(5)
                    ->get();
            }

            if ($hasMatchMethod) {
                $ruleMatches = ChatbotLog::where('match_method', 'rule')->count();
                $semanticMatches = ChatbotLog::where('match_method', 'semantic')->count();
                $similarityMatches = ChatbotLog::where('match_method', 'similarity')->count();
            }
        }

        if (Schema::hasColumn('chatbot_logs', 'agency_id')) {
            $popularAgencies = ChatbotLog::query()
                ->whereNotNull('agency_id')
                ->select('agency_id')
                ->selectRaw('COUNT(*) AS interaction_count')
                ->groupBy('agency_id')
                ->orderByDesc('interaction_count')
                ->with('agency')
                ->limit(5)
                ->get();
        }

        $chatbotMatchMethods = [
            'semantic' => $semanticMatches,
            'similarity' => $similarityMatches,
            'rule' => $ruleMatches,
        ];

        $feedbackBreakdown = [
            'helpful' => $faqFeedbackHelpful,
            'not_helpful' => $faqFeedbackNotHelpful,
        ];

        $faqAnswerRate = $knowledgeQuestions > 0
            ? round(($faqAnswered / $knowledgeQuestions) * 100)
            : 0;

        $fallbackRate = $knowledgeQuestions > 0
            ? round(($fallbackQuestions / $knowledgeQuestions) * 100)
            : 0;

        // Collaboration is operational workload, so include it as a separate
        // signal instead of mixing it into support-request counts.
        $collaborationOpen = CollaborationTask::whereIn('status', ['open', 'in_progress'])->count();
        $collaborationOverdue = CollaborationTask::whereIn('status', ['open', 'in_progress'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();
        $collaborationCompleted = CollaborationTask::where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->count();

        return compact(
            'period',
            'selectedMonth',
            'periodLabel',
            'start',
            'end',
            'totalInquiries',
            'pendingInquiries',
            'awaitingConfirmation',
            'needsFollowUp',
            'openInquiries',
            'answeredInquiries',
            'responseRate',
            'seenAnswers',
            'unseenAnswers',
            'averageResponseTime',
            'inquiryTrend',
            'trendSubmitted',
            'trendAnswered',
            'trendResponseRate',
            'queueStatusCounts',
            'chatbotMatchMethods',
            'feedbackBreakdown',
            'totalAgencies',
            'completeAgencies',
            'incompleteAgencies',
            'totalFaqs',
            'completeFaqs',
            'incompleteFaqs',
            'faqFeedbackHelpful',
            'faqFeedbackNotHelpful',
            'faqFeedbackNeedsReview',
            'faqFeedbackReviewedOpen',
            'faqFeedbackOutstanding',
            'faqFeedbackResolved',
            'faqFeedbackTotal',
            'topSupportAgencies',
            'totalChatbotInteractions',
            'knowledgeQuestions',
            'faqAnswered',
            'faqAnswerRate',
            'fallbackQuestions',
            'fallbackRate',
            'clarificationQuestions',
            'ruleMatches',
            'semanticMatches',
            'popularFaqs',
            'popularAgencies',
            'collaborationOpen',
            'collaborationOverdue',
            'collaborationCompleted'
        );
    }

    /**
     * JSON snapshot used by the Analytics page for background refreshes.
     * The Blade page remains the initial render; this endpoint keeps the
     * visible metrics and lists synchronized without a full page reload.
     */
    public function analyticsDataJson()
    {
        $data = $this->analyticsData(
            request()->query('period', '7d'),
            request()->query('month')
        );

        return response()->json([
            'period' => $data['period'],
            'selected_month' => $data['selectedMonth'],
            'period_label' => $data['periodLabel'],
            'updated_at' => now()->toIso8601String(),
            'metrics' => [
                'total_inquiries' => $data['totalInquiries'],
                'open_inquiries' => $data['openInquiries'],
                'answered_inquiries' => $data['answeredInquiries'],
                'response_rate' => $data['responseRate'],
                'average_response_time' => $data['averageResponseTime'],
                'seen_answers' => $data['seenAnswers'],
                'unseen_answers' => $data['unseenAnswers'],
                'pending' => $data['pendingInquiries'],
                'awaiting_confirmation' => $data['awaitingConfirmation'],
                'needs_follow_up' => $data['needsFollowUp'],
                'trend_submitted' => $data['trendSubmitted'],
                'trend_answered' => $data['trendAnswered'],
                'trend_response_rate' => $data['trendResponseRate'],
                'total_agencies' => $data['totalAgencies'],
                'complete_agencies' => $data['completeAgencies'],
                'incomplete_agencies' => $data['incompleteAgencies'],
                'total_faqs' => $data['totalFaqs'],
                'complete_faqs' => $data['completeFaqs'],
                'incomplete_faqs' => $data['incompleteFaqs'],
                'faq_feedback_helpful' => $data['faqFeedbackHelpful'],
                'faq_feedback_not_helpful' => $data['faqFeedbackNotHelpful'],
                'faq_feedback_needs_review' => $data['faqFeedbackNeedsReview'],
                'faq_feedback_reviewed_open' => $data['faqFeedbackReviewedOpen'],
                'faq_feedback_resolved' => $data['faqFeedbackResolved'],
                'faq_feedback_total' => $data['faqFeedbackTotal'],
                'chatbot_interactions'  => $data['totalChatbotInteractions'],
                'knowledge_questions' => $data['knowledgeQuestions'],
                'faq_answered' => $data['faqAnswered'],
                'faq_answer_rate' => $data['faqAnswerRate'],
                'fallback_questions' => $data['fallbackQuestions'],
                'fallback_rate' => $data['fallbackRate'],
                'clarification_questions' => $data['clarificationQuestions'],
                'rule_matches' => $data['ruleMatches'],
                'semantic_matches' => $data['semanticMatches'],
                'collaboration_open' => $data['collaborationOpen'],
                'collaboration_overdue' => $data['collaborationOverdue'],
                'collaboration_completed' => $data['collaborationCompleted'],
            ],
            'status_counts' => $data['queueStatusCounts'],
            'chatbot_match_methods' => $data['chatbotMatchMethods'],
            'feedback_breakdown' => $data['feedbackBreakdown'],
            'trend' => $data['inquiryTrend'],
            'support_agencies' => $data['topSupportAgencies']->map(fn ($item) => [
                'name' => $item->agency?->agency_name ?? 'Unassigned agency',
                'count' => (int) $item->request_count,
            ])->values(),
            'popular_faqs' => $data['popularFaqs']->map(fn ($item) => [
                'question' => $item->faq?->question ?? 'FAQ no longer available',
                'agency' => $item->faq?->agency?->agency_name,
                'count' => (int) $item->usage_count,
            ])->values(),
            'popular_agencies' => $data['popularAgencies']->map(fn ($item) => [
                'name' => $item->agency?->agency_name ?? 'Agency no longer available',
                'count' => (int) $item->interaction_count,
            ])->values(),
        ]);
    }

    /**
     * Aggregate ratings globally while counting review work once per FAQ.
     * Individual dislike records are evidence, not separate admin tasks.
     */
    private function faqFeedbackSummary(): array
    {
        $summary = FaqFeedback::query()
            ->selectRaw("
                COUNT(*) AS total,
                SUM(CASE WHEN rating = 'helpful' THEN 1 ELSE 0 END) AS helpful,
                SUM(CASE WHEN rating = 'not_helpful' THEN 1 ELSE 0 END) AS not_helpful
            ")
            ->first();

        $minimumRatings = (int) config('faq_feedback.minimum_ratings_for_review', 5);
        $needsReview = FaqFeedback::query()
            ->join('faqs', 'faqs.id', '=', 'faq_feedback.faq_id')
            ->whereNull('faqs.deleted_at')
            ->whereColumn('faq_feedback.faq_version_id', 'faqs.current_version_id')
            ->whereNotNull('faq_feedback.faq_id')
            ->select('faq_feedback.faq_id')
            ->groupBy('faq_feedback.faq_id')
            ->havingRaw('COUNT(*) >= ?', [$minimumRatings])
            ->havingRaw("SUM(CASE WHEN faq_feedback.rating = 'not_helpful' THEN 1 ELSE 0 END) > SUM(CASE WHEN faq_feedback.rating = 'helpful' THEN 1 ELSE 0 END)")
            ->get()
            ->count();

        return [
            'total' => (int) ($summary->total ?? 0),
            'helpful' => (int) ($summary->helpful ?? 0),
            'not_helpful' => (int) ($summary->not_helpful ?? 0),
            'needs_review' => $needsReview,
            // Kept as zero-valued compatibility fields for existing report data bindings.
            'reviewed_open' => 0,
            'resolved' => 0,
        ];
    }

    /**
     * Use a database aggregate for average response time.
     *
     * Use each supported database driver's native date-difference
     * expression, with a collection fallback for SQLite tests.
     */
    private function averageResponseMinutes(): ?int
    {
        $query = SupportRequest::query()
            ->where('status', 'answered')
            ->whereNotNull('created_at')
            ->whereNotNull('answered_at');

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $average = $query->selectRaw(
                'AVG(TIMESTAMPDIFF(MINUTE, created_at, answered_at)) AS average_minutes'
            )->value('average_minutes');

            return $average === null ? null : (int) round((float) $average);
        }

        if ($driver === 'pgsql') {
            $average = $query->selectRaw(
                'AVG(EXTRACT(EPOCH FROM (answered_at - created_at)) / 60.0) AS average_minutes'
            )->value('average_minutes');

            return $average === null ? null : (int) round((float) $average);
        }

        $rows = $query->select([
            'created_at',
            'answered_at',
        ])->limit(5000)->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return (int) round(
            $rows->avg(
                fn ($row) =>
                    Carbon::parse($row->created_at)
                        ->diffInMinutes(Carbon::parse($row->answered_at))
            )
        );
    }

    /**
     * Format a nullable minute count for a compact admin metric.
     */
    private function formatMinutes(?int $minutes): ?string
    {
        if ($minutes === null) {
            return null;
        }

        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        return $remaining === 0
            ? $hours . ' hr'
            : $hours . ' hr ' . $remaining . ' min';
    }

    /**
     * Apply the same activity visibility policy used by Activity Logs.
     *
     * Regular admins see:
     * - their own administrative activity;
     * - public-user activity.
     *
     * Superadmins see all activity.
     *
     * Administrative actions that represent meaningful data-management
     * work for the dashboard and audit system.
     */
    private function administrativeActions(): array
    {
        return config('activity_logs.admin_actions', []);
    }

    /**
     * Meaningful work events used by the dashboard collaboration summary.
     * Authentication events are intentionally excluded.
     */
    private function collaborationActions(): array
    {
        return array_values(array_diff(
            $this->administrativeActions(),
            ['admin_login', 'admin_logout']
        ));
    }

    private function authorizedActivityQuery()
    {


        $query = UserLog::with([
            'user',
            'agency',
            'category',
            'targetUser',
        ]);

        if (auth()->user()->role === 'admin') {
            $currentAdminId = auth()->id();

            $adminActions = $this->administrativeActions();

            $query->where(function ($query) use (
                $currentAdminId,
                $adminActions
            ) {
                $query
                    ->where('user_id', $currentAdminId)
                    ->orWhere(function ($subQuery) use ($adminActions) {
                        $subQuery
                            ->where('role', 'user')
                            ->whereNotIn('action', $adminActions);
                    });
            });
        }

        return $query;
    }
}
