<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\ChatbotLog;
use App\Models\Faq;
use App\Models\SupportRequest;
use App\Models\User;
use App\Models\UserLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        return view('admin.analytics', $this->analyticsData());
    }

    /**
     * Export a combined operational/analytics report.
     */
    public function exportPdf()
    {
        $data = array_merge(
            $this->dashboardData(),
            $this->analyticsData()
        );

        $pdf = Pdf::loadView('admin.report', $data);

        return $pdf->download(
            'KNOWURLOCAL_Admin_Report_' . now()->format('Y-m-d') . '.pdf'
        );
    }

    /**
     * Build lightweight dashboard data.
     *
     * Important performance rule:
     * dashboard summary cards use COUNT/EXISTS queries instead of
     * loading full tables into PHP.
     */
    private function dashboardData(): array
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

        $faqCountsByAgency = Faq::query()
            ->whereNotNull('agency_id')
            ->whereHas('agency')
            ->select('agency_id')
            ->selectRaw('COUNT(*) AS faq_count')
            ->groupBy('agency_id')
            ->orderByDesc('faq_count')
            ->get();

        $topFaqCount = (int) ($faqCountsByAgency->first()->faq_count ?? 0);

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

        $totalUsers = User::where('role', 'user')->count();

        $totalAdmins = User::whereIn(
            'role',
            ['admin', 'superadmin']
        )->count();

        $totalInquiries = SupportRequest::count();

        $pendingInquiries = SupportRequest::where(
            'status',
            'pending'
        )->count();

        $answeredInquiries = SupportRequest::where(
            'status',
            'answered'
        )->count();

        $pendingInquiryPercentage = $totalInquiries > 0
            ? round(($pendingInquiries / $totalInquiries) * 100)
            : 0;

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
            $incompleteFaqs;

        $answeredToday = SupportRequest::query()
            ->where('status', 'answered')
            ->whereDate('answered_at', today())
            ->count();

        $teamRespondersToday = UserLog::query()
            ->with('user:id,first_name,last_name,role')
            ->whereDate('created_at', today())
            ->whereIn('action', [
                'answer_support_request',
                'forward_support_response',
            ])
            ->whereHas('user', function ($query) {
                $query->whereIn('role', ['admin', 'superadmin']);
            })
            ->latest()
            ->get()
            ->unique('user_id')
            ->take(5)
            ->values();

        $recentTeamActivity = UserLog::query()
            ->with('user:id,first_name,last_name,role')
            ->whereIn('action', [
                'answer_support_request',
                'forward_support_response',
                'delete_support_request',
                'restore_support_request',
            ])
            ->latest()
            ->limit(5)
            ->get();

        $recentActivity = $this->authorizedActivityQuery()
            ->latest()
            ->limit(8)
            ->get();

        return compact(
            'totalAgencies',
            'totalNGA',
            'totalNGO',
            'totalFaqs',
            'topFaqCount',
            'topFaqContributorTieCount',
            'topFaqContributors',
            'totalUsers',
            'totalAdmins',
            'totalInquiries',
            'pendingInquiries',
            'answeredInquiries',
            'pendingInquiryPercentage',
            'incompleteAgencies',
            'completeAgencies',
            'incompleteFaqs',
            'completeFaqs',
            'totalNeedsAttention',
            'answeredToday',
            'teamRespondersToday',
            'recentTeamActivity',
            'recentActivity'
        );
    }

    /**
     * Build only the data required by the analytics page/report.
     *
     * Trend counts are aggregated by the database rather than loading
     * every request/log row into application memory.
     */
    private function analyticsData(): array
    {
        $totalInquiries = SupportRequest::count();

        $answeredInquiries = SupportRequest::where(
            'status',
            'answered'
        )->count();

        $responseRate = $totalInquiries > 0
            ? round(($answeredInquiries / $totalInquiries) * 100)
            : 0;

        $seenAnswers = SupportRequest::query()
            ->where('status', 'answered')
            ->whereNotNull('answer_seen_at')
            ->count();

        $unseenAnswers = SupportRequest::query()
            ->where('status', 'answered')
            ->whereNull('answer_seen_at')
            ->count();

        $averageResponseMinutes = $this->averageResponseMinutes();

        $averageResponseTime = $this->formatMinutes(
            $averageResponseMinutes
        );

        $start = now()->startOfDay()->subDays(6);
        $end = now()->endOfDay();

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

        $inquiryTrend = collect(range(0, 6))
            ->map(function (int $day) use (
                $start,
                $submittedByDay,
                $answeredByDay
            ) {
                $date = $start->copy()->addDays($day);
                $key = $date->format('Y-m-d');

                return [
                    'date' => $key,
                    'label' => $date->format('D'),
                    'submitted' => (int) ($submittedByDay[$key] ?? 0),
                    'answered' => (int) ($answeredByDay[$key] ?? 0),
                ];
            })
            ->all();

        $totalChatbotInteractions = ChatbotLog::count();

        $knowledgeQuestions = ChatbotLog::whereIn(
            'outcome',
            [
                'answered',
                'fallback',
                'clarification',
                'wrong_agency',
            ]
        )->count();

        $faqAnswered = ChatbotLog::query()
            ->where('outcome', 'answered')
            ->whereNotNull('faq_id')
            ->count();

        $faqAnswerRate = $knowledgeQuestions > 0
            ? round(($faqAnswered / $knowledgeQuestions) * 100)
            : 0;

        $fallbackQuestions = ChatbotLog::where(
            'outcome',
            'fallback'
        )->count();

        $fallbackRate = $knowledgeQuestions > 0
            ? round(($fallbackQuestions / $knowledgeQuestions) * 100)
            : 0;

        $clarificationQuestions = ChatbotLog::where(
            'outcome',
            'clarification'
        )->count();

        $ruleMatches = ChatbotLog::where(
            'match_method',
            'rule'
        )->count();

        $semanticMatches = ChatbotLog::where(
            'match_method',
            'ai'
        )->count();

        $popularFaqs = ChatbotLog::query()
            ->where('outcome', 'answered')
            ->whereNotNull('faq_id')
            ->select('faq_id')
            ->selectRaw('COUNT(*) AS usage_count')
            ->groupBy('faq_id')
            ->orderByDesc('usage_count')
            ->with('faq')
            ->limit(5)
            ->get();

        $popularAgencies = ChatbotLog::query()
            ->whereNotNull('agency_id')
            ->select('agency_id')
            ->selectRaw('COUNT(*) AS interaction_count')
            ->groupBy('agency_id')
            ->orderByDesc('interaction_count')
            ->with('agency')
            ->limit(5)
            ->get();

        return compact(
            'totalInquiries',
            'answeredInquiries',
            'responseRate',
            'seenAnswers',
            'unseenAnswers',
            'averageResponseTime',
            'inquiryTrend',
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
            'popularAgencies'
        );
    }

    /**
     * Use a database aggregate for average response time.
     *
     * MySQL/MariaDB are the production targets for KNOWURLOCAL.
     * The fallback keeps local SQLite-based tests functional.
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

            return $average === null ? null : (int) round($average);
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
     */
    private function authorizedActivityQuery()
    {
        $adminActions = [
            'admin_login',
            'admin_logout',
            'create_agency',
            'update_agency',
            'trash_agency',
            'restore_agency',
            'force_delete_agency',
            'delete_agency',
            'create_faq',
            'update_faq',
            'delete_faq',
            'restore_faq',
            'force_delete_faq',
            'create_category',
            'update_category',
            'delete_category',
            'restore_category',
            'force_delete_category',
            'delete_support_request',
            'restore_support_request',
            'force_delete_support_request',
            'approve_admin',
            'invite_admin',
            'promote_admin',
            'demote_admin',
            'deactivate_admin',
            'reactivate_admin',
            'delete_admin',
            'deactivate_user',
            'reactivate_user',
            'delete_user',
        ];

        $query = UserLog::with([
            'user',
            'agency',
            'category',
            'targetUser',
        ]);

        if (auth()->user()->role === 'admin') {
            $currentAdminId = auth()->id();

            $query->where(function ($query) use (
                $currentAdminId,
                $adminActions
            ) {
                $query
                    ->where('user_id', $currentAdminId)
                    ->orWhere(function ($subQuery) use ($adminActions) {
                        $subQuery
                            ->whereHas('user', function ($userQuery) {
                                $userQuery->where('role', 'user');
                            })
                            ->whereNotIn('action', $adminActions);
                    });
            });
        }

        return $query;
    }
}
