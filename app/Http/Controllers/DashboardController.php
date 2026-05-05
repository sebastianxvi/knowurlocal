<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\ChatbotLog; // 🔥 IMPORTANT
use App\Models\Faq;
use App\Models\UserLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        /* ========================
           📊 TOTAL AGENCIES
        ======================== */
        $totalAgencies = Agency::count();

        /* ========================
           📊 TOTAL FAQS
        ======================== */
        $totalFaqs = Faq::count();

        /* ========================
           📊 TOTAL CHATBOT QUERIES
        ======================== */
        $totalChats = ChatbotLog::count();

        /* ========================
        📊 TOTAL CHATBOT QUERIES
        ======================== */
        $totalChats = ChatbotLog::count();

        /* ========================
        ❌ FAILED (FALLBACK)
        ======================== */
        $fallbackCount = ChatbotLog::where('type', 'fallback')->count();

        /* ========================
        ✅ ACCURACY (%)
        ======================== */
        $accuracy = $totalChats > 0
            ? round((($totalChats - $fallbackCount) / $totalChats) * 100, 2)
            : 0;


        /* ========================
        📅 TODAY CHATS
        ======================== */
        $todayChats = ChatbotLog::whereDate('created_at', today())->count();

        /* ========================
        📆 WEEK CHATS
        ======================== */
        $weekChats = ChatbotLog::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();

        $failedQueries = $fallbackCount;


        /* ========================
        📉 FALLBACK RATE (%)
        ======================== */
        $fallbackRate = $totalChats > 0
            ? round(($fallbackCount / $totalChats) * 100, 2)
            : 0;

        
        /* ========================
        🧠 TOP QUESTION (FILTERED)
        ======================== */

        // common noise words
        $ignoredQuestions = [
            'hi',
            'hello',
            'hey',
            'thanks',
            'thank you',
            'ok',
            'okay'
        ];

        $topQuestionData = ChatbotLog::select('question')
            ->selectRaw('COUNT(*) as count')
            ->whereNotIn(DB::raw('LOWER(question)'), $ignoredQuestions)
            ->groupBy('question')
            ->orderByDesc('count')
            ->first();

        $topQuestion = $topQuestionData->question ?? '-';
        $topQuestionCount = $topQuestionData->count ?? 0;


        /* ========================
        🏢 TOP AGENCY
        ======================== */
        $topAgencyData = ChatbotLog::join('agencies', 'chatbot_logs.agency_id', '=', 'agencies.id')
            ->select('agencies.agency_name')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('chatbot_logs.agency_id') // avoid null agencies
            ->groupBy('agencies.agency_name')
            ->orderByDesc('count')
            ->first();


        $topAgency = $topAgencyData->agency_name ?? '-';
        $topAgencyCount = $topAgencyData->count ?? 0;


        /* ========================
        ⏰ PEAK HOUR
        ======================== */
        $peakHourData = ChatbotLog::selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderByDesc('count')
            ->first();

        $peakHour = isset($peakHourData->hour)
        ? date("g A", strtotime($peakHourData->hour . ":00"))
        : '-';


        /* ========================
        👥 ACTIVE USERS TODAY
        ======================== */
        $activeUsersToday = ChatbotLog::whereDate('created_at', today())
            ->whereNotNull('user_id') // ignore guests
            ->distinct('user_id')
            ->count('user_id');


        /* ========================
        🔐 ADMIN LOGINS TODAY
        ======================== */
        $adminLogins = UserLog::where('action', 'login')
            ->whereIn('role', ['admin', 'superadmin']) // only admins
            ->whereDate('created_at', today())
            ->count();

        /* ========================
        📊 ADMIN CHANGES TODAY
        ======================== */
        $changesToday = UserLog::whereIn('role', ['admin', 'superadmin'])
            ->where('action', '!=', 'login') // exclude login (we already counted it)
            ->whereDate('created_at', today())
            ->count();


        /* ========================
        🧠 MOST ACTIVE ADMIN
        ======================== */
        $mostActiveAdminData = UserLog::join('users', 'user_logs.user_id', '=', 'users.id')
            ->selectRaw('CONCAT(users.first_name, " ", users.last_name) as name')
            ->selectRaw('COUNT(*) as count')
            ->whereIn('user_logs.role', ['admin', 'superadmin'])
            ->whereDate('user_logs.created_at', today())
            ->groupBy('user_logs.user_id', 'users.first_name', 'users.last_name')
            ->orderByDesc('count')
            ->first();

        $mostActiveAdmin = $mostActiveAdminData->name ?? '-';
        $mostActiveAdminCount = $mostActiveAdminData->count ?? 0;


        /* ========================
        🕒 LAST ADMIN ACTION
        ======================== */
        $lastActionData = UserLog::join('users', 'user_logs.user_id', '=', 'users.id')
            ->select(
                'user_logs.action',
                'users.first_name',
                'users.last_name'
            )
            ->whereIn('user_logs.role', ['admin', 'superadmin'])
            ->orderByDesc('user_logs.created_at')
            ->first();


        $lastAction = $lastActionData->action ?? '-';

        $lastActionUser = isset($lastActionData)
            ? $lastActionData->first_name . ' ' . $lastActionData->last_name
            : '-';



        $totalUsers = ChatbotLog::whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $totalUserQueries = ChatbotLog::whereNotNull('user_id')->count();

        /* ========================
        📊 AVG QUERIES PER USER
        ======================== */
        $avgQueries = $totalUsers > 0
            ? round($totalUserQueries / $totalUsers, 2)
            : 0;


        /* ========================
        📈 CHAT TREND (LAST 7 DAYS)
        ======================== */
        $chatTrend = ChatbotLog::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay()
            ])
            ->groupBy('date')
            ->orderBy('date')
            ->get();


        /* ========================
        🏢 AGENCY USAGE DATA
        ======================== */
        $agencyData = ChatbotLog::join('agencies', 'chatbot_logs.agency_id', '=', 'agencies.id')
            ->select('agencies.agency_name')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('chatbot_logs.agency_id')
            ->groupBy('agencies.agency_name')
            ->orderByDesc('count')
            ->limit(5) // top 5 agencies
            ->get();


        /* ========================
        📊 RESPONSE DISTRIBUTION
        ======================== */
        $faqCount = ChatbotLog::where('type', 'faq')->count();
        $otherCount = ChatbotLog::whereNotIn('type', ['faq', 'fallback'])->count();


        $responseLabels = collect(['FAQ', 'Fallback', 'Other']);
        $responseValues = collect([
            $faqCount,
            $fallbackCount,
            $otherCount
        ]);


        /* ========================
        📊 FEATURE USAGE
        ======================== */

        $featureLabels = ['FAQ Answered', 'Fallback', 'Other'];

        $faqCount = ChatbotLog::where('type', 'faq')->count();

        $fallbackCount = ChatbotLog::where('type', 'fallback')->count();

        $otherCount = ChatbotLog::whereNotIn('type', ['faq','fallback'])->count();

        $featureValues = [
            $faqCount,
            $fallbackCount,
            $otherCount
        ]; 


        /* ========================
        🧠 TOP QUESTIONS (LIST)
        ======================== */
        $topQuestions = ChatbotLog::select('question', DB::raw('COUNT(*) as count'))
            ->whereNotNull('question')
            ->groupBy('question')
            ->orderByDesc('count')
            ->limit(5)
            ->get();


        /* ========================
        🕒 RECENT ACTIVITY
        ======================== */
        $recentLogs = UserLog::with('user')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();


        /* ========================
        📈 GROWTH RATE (WEEKLY)
        ======================== */

        // current week
        $currentWeek = ChatbotLog::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();

        // previous week
        $previousWeek = ChatbotLog::whereBetween('created_at', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek()
        ])->count();

        // calculate growth safely
        $growthRate = $previousWeek > 0
            ? round((($currentWeek - $previousWeek) / $previousWeek) * 100, 2)
            : 0;


        /* ========================
        📊 FAQ RESOLVED COUNT
        ======================== */
        $faqCount = ChatbotLog::where('type', 'faq')->count();


        /* ========================
        🔥 HEATMAP DATA
        ======================== */

        $heatmapRaw = ChatbotLog::select(
                DB::raw('DAYOFWEEK(created_at) as day'),
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('day','hour')
            ->get();


        $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

        $heatmap = [];

        foreach ($days as $index => $dayName) {

            $dayIndex = $index + 1; // MySQL: Sunday = 1

            $dayData = [];

            for ($hour = 0; $hour < 24; $hour++) {

                $match = $heatmapRaw
                    ->where('day', $dayIndex)
                    ->where('hour', $hour)
                    ->first();

                $dayData[] = $match ? $match->count : 0;
            }

            $heatmap[] = [
                'name' => $dayName,
                'data' => $dayData
            ];
        }

        return view('admin.dashboard', compact(
            'totalAgencies',
            'totalFaqs',
            'totalChats',
            'accuracy',

            'todayChats',
            'weekChats',

            'failedQueries',
            'fallbackRate',

            // 🔥 ADD THESE
            'topQuestion',
            'topQuestionCount',

            'topAgency',
            'topAgencyCount',
            'peakHour',  

            'activeUsersToday',
            'adminLogins',
            'changesToday',

            'mostActiveAdmin',
            'mostActiveAdminCount',
            'lastAction',
            'lastActionUser',

            'avgQueries',
            'chatTrend',

            'agencyData',
            'responseLabels',
            'responseValues',

            'featureLabels',
            'featureValues',

            'topQuestions',
            'recentLogs',

            'growthRate',
            'faqCount',         // ✅ ADD THIS
            'fallbackCount', 
            'heatmap',
        ));
    }

    public function exportPdf()
{
    /* ========================
       📊 CORE METRICS
    ======================== */

    $totalAgencies = Agency::count();
    $totalFaqs = Faq::count();
    $totalChats = ChatbotLog::count();

    $fallbackCount = ChatbotLog::where('type', 'fallback')->count();
    $faqCount = ChatbotLog::where('type', 'faq')->count();

    $accuracy = $totalChats > 0
        ? round((($totalChats - $fallbackCount) / $totalChats) * 100, 2)
        : 0;

    /* ========================
       📅 TIME DATA
    ======================== */

    $todayChats = ChatbotLog::whereDate('created_at', today())->count();

    $weekChats = ChatbotLog::whereBetween('created_at', [
        now()->startOfWeek(),
        now()->endOfWeek()
    ])->count();

    $fallbackRate = $totalChats > 0
        ? round(($fallbackCount / $totalChats) * 100, 2)
        : 0;

    /* ========================
       🧠 TOP QUESTION
    ======================== */

    $topQuestionData = ChatbotLog::select('question')
        ->selectRaw('COUNT(*) as count')
        ->groupBy('question')
        ->orderByDesc('count')
        ->first();

    $topQuestion = $topQuestionData->question ?? '-';
    $topQuestionCount = $topQuestionData->count ?? 0;

    /* ========================
       🏢 TOP AGENCY
    ======================== */

    $topAgencyData = ChatbotLog::select('agency_id')
        ->selectRaw('COUNT(*) as count')
        ->whereNotNull('agency_id')
        ->groupBy('agency_id')
        ->orderByDesc('count')
        ->with('agency')
        ->first();

    $topAgency = $topAgencyData->agency->agency_name ?? '-';

    /* ========================
       📦 PASS DATA
    ======================== */

    $data = compact(
        'totalAgencies',
        'totalFaqs',
        'totalChats',
        'accuracy',
        'todayChats',
        'weekChats',
        'fallbackRate',
        'fallbackCount',
        'faqCount',
        'topQuestion',
        'topQuestionCount',
        'topAgency'
    );

    $pdf = Pdf::loadView('admin.report', $data);

    return $pdf->download('knowurlocal-report.pdf');
}
}