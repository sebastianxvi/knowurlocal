<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Faq;
use App\Models\SupportRequest;
use App\Models\User;
use App\Models\UserLog;
use App\Models\ChatbotLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the administrator dashboard.
     *
     * The dashboard and PDF report intentionally use the
     * same data source so their numbers cannot drift apart.
     */
    public function index()
    {
        return view(
            'admin.dashboard',
            $this->dashboardData()
        );
    }


    /**
     * Export the current dashboard information as a PDF.
     *
     * The report uses the exact same dataset as the dashboard.
     */
    public function exportPdf()
    {
        /*
         * Build the dashboard dataset once.
         *
         * This prevents the PDF from having its own separate
         * analytics calculations.
         */
        $data = $this->dashboardData();


        /*
         * Render the dedicated PDF Blade view.
         *
         * The dashboard and PDF have different presentation
         * requirements, so the report gets its own Blade view.
         */
        $pdf = Pdf::loadView(
            'admin.report',
            $data
        );


        /*
         * Use a predictable filename containing the report date.
         *
         * The date comes from the server rather than user input,
         * so there is no filename injection risk.
         */
        $filename =
            'KNOWURLOCAL_Admin_Report_' .
            now()->format('Y-m-d') .
            '.pdf';


        /*
         * Return the generated PDF as a download response.
         */
        return $pdf->download($filename);
    }


    /**
     * Build the complete dataset used by the dashboard
     * and administrative PDF report.
     *
     * Keeping these calculations in one method gives us
     * a single source of truth for reporting.
     */
    private function dashboardData(): array
    {
        /*
         * =====================================================
         * OVERVIEW
         * =====================================================
         */

        $totalAgencies = Agency::count();

        $totalFaqs = Faq::count();

        $totalUsers = User::where(
            'role',
            'user'
        )->count();

        $totalAdmins = User::whereIn(
            'role',
            ['admin', 'superadmin']
        )->count();


        /*
         * =====================================================
         * USER INQUIRIES
         * =====================================================
         */

        $totalInquiries = SupportRequest::count();

        $pendingInquiries = SupportRequest::where(
            'status',
            'pending'
        )->count();

        $answeredInquiries = SupportRequest::where(
            'status',
            'answered'
        )->count();


        /*
         * Calculate the percentage of inquiries currently
         * waiting for an administrator's response.
         */
        $pendingInquiryPercentage = $totalInquiries > 0
            ? round(
                ($pendingInquiries / $totalInquiries) * 100
            )
            : 0;


        /*
         * =====================================================
         * INQUIRY ANALYTICS
         * =====================================================
         */

        /*
         * RESPONSE RATE
         */
        $responseRate = $totalInquiries > 0
            ? round(
                ($answeredInquiries / $totalInquiries) * 100
            )
            : 0;


        /*
         * ANSWER VISIBILITY
         */
        $seenAnswers = SupportRequest::where(
            'status',
            'answered'
        )
        ->whereNotNull(
            'answer_seen_at'
        )
        ->count();


        $unseenAnswers = SupportRequest::where(
            'status',
            'answered'
        )
        ->whereNull(
            'answer_seen_at'
        )
        ->count();


        /*
         * AVERAGE RESPONSE TIME
         */
        $answeredRequests = SupportRequest::where(
            'status',
            'answered'
        )
        ->whereNotNull(
            'answered_at'
        )
        ->whereNotNull(
            'created_at'
        )
        ->get([
            'created_at',
            'answered_at',
        ]);


        $averageResponseMinutes = null;

        if ($answeredRequests->isNotEmpty()) {

            $totalResponseMinutes = $answeredRequests->sum(
                function ($request) {

                    return Carbon::parse(
                        $request->created_at
                    )->diffInMinutes(
                        Carbon::parse(
                            $request->answered_at
                        )
                    );
                }
            );


            $averageResponseMinutes = round(
                $totalResponseMinutes /
                $answeredRequests->count()
            );
        }


        /*
         * Convert the average response time into a
         * human-readable value.
         */
        $averageResponseTime = null;

        if ($averageResponseMinutes !== null) {

            if ($averageResponseMinutes < 60) {

                $averageResponseTime =
                    $averageResponseMinutes . ' min';

            } else {

                $hours = intdiv(
                    $averageResponseMinutes,
                    60
                );

                $minutes = $averageResponseMinutes % 60;


                if ($minutes === 0) {

                    $averageResponseTime =
                        $hours . ' hr';

                } else {

                    $averageResponseTime =
                        $hours . ' hr ' .
                        $minutes . ' min';
                }
            }
        }


        /*
         * =====================================================
         * INQUIRY TREND — LAST 7 DAYS
         * =====================================================
         */

        $analyticsStartDate = now()
            ->startOfDay()
            ->subDays(6);

        $analyticsEndDate = now()
            ->endOfDay();


        $recentInquiries = SupportRequest::whereBetween(
            'created_at',
            [
                $analyticsStartDate,
                $analyticsEndDate,
            ]
        )
        ->get([
            'created_at',
        ]);


        $recentAnswers = SupportRequest::where(
            'status',
            'answered'
        )
        ->whereNotNull(
            'answered_at'
        )
        ->whereBetween(
            'answered_at',
            [
                $analyticsStartDate,
                $analyticsEndDate,
            ]
        )
        ->get([
            'answered_at',
        ]);


        $inquiryTrend = [];


        for ($day = 0; $day < 7; $day++) {

            $date = $analyticsStartDate
                ->copy()
                ->addDays($day);


            $submitted = $recentInquiries
                ->filter(function ($request) use ($date) {

                    return Carbon::parse(
                        $request->created_at
                    )->isSameDay($date);

                })
                ->count();


            $answered = $recentAnswers
                ->filter(function ($request) use ($date) {

                    return Carbon::parse(
                        $request->answered_at
                    )->isSameDay($date);

                })
                ->count();


            $inquiryTrend[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('D'),
                'submitted' => $submitted,
                'answered' => $answered,
            ];
        }


        /*
         * =====================================================
         * CHATBOT / KNOWLEDGE BASE ANALYTICS
         * =====================================================
         */

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


        $faqAnswered = ChatbotLog::where(
            'outcome',
            'answered'
        )
        ->whereNotNull(
            'faq_id'
        )
        ->count();


        $faqAnswerRate = $knowledgeQuestions > 0
            ? round(
                ($faqAnswered / $knowledgeQuestions) * 100
            )
            : 0;


        $fallbackQuestions = ChatbotLog::where(
            'outcome',
            'fallback'
        )->count();


        $fallbackRate = $knowledgeQuestions > 0
            ? round(
                ($fallbackQuestions / $knowledgeQuestions) * 100
            )
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
            'semantic'
        )->count();


        /*
         * MOST USED FAQs
         */
        $popularFaqs = ChatbotLog::query()
            ->where(
                'outcome',
                'answered'
            )
            ->whereNotNull(
                'faq_id'
            )
            ->select('faq_id')
            ->selectRaw(
                'COUNT(*) as usage_count'
            )
            ->groupBy('faq_id')
            ->orderByDesc('usage_count')
            ->with('faq')
            ->limit(5)
            ->get();


        /*
         * MOST REQUESTED AGENCIES
         */
        $popularAgencies = ChatbotLog::query()
            ->whereNotNull(
                'agency_id'
            )
            ->select('agency_id')
            ->selectRaw(
                'COUNT(*) as interaction_count'
            )
            ->groupBy('agency_id')
            ->orderByDesc('interaction_count')
            ->with('agency')
            ->limit(5)
            ->get();


        /*
         * =====================================================
         * AGENCY DATA HEALTH
         * =====================================================
         */

        $incompleteAgencies = Agency::where(function ($query) {

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

        })->count();


        $completeAgencies =
            $totalAgencies - $incompleteAgencies;


        /*
         * =====================================================
         * FAQ DATA HEALTH
         * =====================================================
         */

        $incompleteFaqs = Faq::where(function ($query) {

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

        })->count();


        $completeFaqs =
            $totalFaqs - $incompleteFaqs;


        /*
        * =====================================================
        * RECENT SYSTEM ACTIVITY
        * =====================================================
        *
        * The dashboard follows the same visibility rule as the
        * dedicated Activity Logs page.
        *
        * Regular administrators:
        *
        * - Can see their own administrative activity.
        * - Can see normal public-user activity.
        * - Cannot see another administrator's activity.
        *
        * Superadmins:
        *
        * - Can see all activity.
        */
        $adminActions = [

            /*
            * Authentication
            */
            'admin_login',
            'admin_logout',

            /*
            * Agency management
            */
            'create_agency',
            'update_agency',
            'trash_agency',
            'restore_agency',
            'force_delete_agency',
            'delete_agency',

            /*
            * FAQ management
            */
            'create_faq',
            'update_faq',
            'delete_faq',
            'restore_faq',
            'force_delete_faq',

            /*
            * Category management
            */
            'create_category',
            'update_category',
            'delete_category',
            'restore_category',
            'force_delete_category',

            /*
            * Support Request management
            */
            'delete_support_request',
            'restore_support_request',
            'force_delete_support_request',

            /*
            * Administrator management
            */
            'approve_admin',
            'invite_admin',
            'promote_admin',
            'demote_admin',
            'deactivate_admin',
            'reactivate_admin',
            'delete_admin',

            /*
            * Public User Management
            */
            'deactivate_user',
            'reactivate_user',
            'delete_user',
        ];


        /*
        * Start with the complete audit-log query.
        *
        * Eager loading prevents additional queries when the Blade
        * accesses related users, agencies, categories, or targets.
        */
        $recentActivityQuery = UserLog::with([
            'user',
            'agency',
            'category',
            'targetUser',
        ]);


        /*
        * Apply the same visibility boundary used by the
        * dedicated Activity Logs page.
        */
        if (auth()->user()->role === 'admin') {

            $currentAdminId = auth()->id();

            $recentActivityQuery->where(function ($query) use (
                $currentAdminId,
                $adminActions
            ) {

                /*
                * The administrator can always see their own logs.
                */
                $query->where(
                    'user_id',
                    $currentAdminId
                )

                /*
                * Normal public-user activity remains visible.
                *
                * Administrative actions are excluded from this
                * branch so a normal admin cannot see another
                * administrator's logs.
                */
                ->orWhere(function ($subQuery) use (
                    $adminActions
                ) {

                    $subQuery->whereHas(
                        'user',
                        function ($userQuery) {

                            $userQuery->where(
                                'role',
                                'user'
                            );
                        }
                    )

                    ->whereNotIn(
                        'action',
                        $adminActions
                    );
                });
            });
        }


        /*
        * Get only the latest eight records AFTER applying the
        * authorization filter.
        *
        * This is important.
        *
        * We must filter first and limit second.
        *
        * Otherwise a Superadmin's recent activity could occupy
        * the eight newest records and cause the dashboard to
        * appear empty for a regular administrator.
        */
        $recentActivity = $recentActivityQuery
            ->latest()
            ->limit(8)
            ->get();


        /*
         * =====================================================
         * RETURN SHARED REPORT DATA
         * =====================================================
         *
         * Every value returned here can be consumed by both:
         *
         * - admin.dashboard
         * - admin.report
         */

        return [

            /*
             * Overview
             */
            'totalAgencies' => $totalAgencies,
            'totalFaqs' => $totalFaqs,
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,


            /*
             * Inquiry workload
             */
            'totalInquiries' => $totalInquiries,
            'pendingInquiries' => $pendingInquiries,
            'answeredInquiries' => $answeredInquiries,
            'pendingInquiryPercentage' => $pendingInquiryPercentage,


            /*
             * Inquiry analytics
             */
            'responseRate' => $responseRate,
            'seenAnswers' => $seenAnswers,
            'unseenAnswers' => $unseenAnswers,
            'averageResponseTime' => $averageResponseTime,
            'inquiryTrend' => $inquiryTrend,


            /*
             * Chatbot / knowledge base analytics
             */
            'totalChatbotInteractions' => $totalChatbotInteractions,
            'knowledgeQuestions' => $knowledgeQuestions,
            'faqAnswered' => $faqAnswered,
            'faqAnswerRate' => $faqAnswerRate,
            'fallbackQuestions' => $fallbackQuestions,
            'fallbackRate' => $fallbackRate,
            'clarificationQuestions' => $clarificationQuestions,
            'ruleMatches' => $ruleMatches,
            'semanticMatches' => $semanticMatches,
            'popularFaqs' => $popularFaqs,
            'popularAgencies' => $popularAgencies,


            /*
             * Data health
             */
            'incompleteAgencies' => $incompleteAgencies,
            'completeAgencies' => $completeAgencies,
            'incompleteFaqs' => $incompleteFaqs,
            'completeFaqs' => $completeFaqs,


            /*
             * Activity
             */
            'recentActivity' => $recentActivity,
        ];
    }
}