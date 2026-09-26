<?php

namespace App\Http\Controllers;

use App\Models\ChatbotLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ChatbotLogController extends Controller
{
    /**
     * Display chatbot interaction logs.
     */
    public function index(Request $request)
    {
        /*
         * Bound all filters before they reach the query builder.
         * This prevents oversized search payloads and invalid date input.
         */
        $request->validate([
            'sort' => ['nullable', 'in:asc,desc'],
            'outcome' => ['nullable', 'string', 'max:40'],
            'match_method' => ['nullable', 'string', 'max:20'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        /*
         * 🔒 SORT SECURITY
         *
         * Only allow known sort directions.
         * Never pass arbitrary request input directly
         * into orderBy().
         */
        $sort = $request->get('sort', 'desc');

        $sort = in_array($sort, ['asc', 'desc'], true)
            ? $sort
            : 'desc';


        /*
         * 🔧 BASE QUERY
         *
         * Eager-load related records so the Blade view
         * does not repeatedly query the database for
         * every row.
         */
        $query = ChatbotLog::with([
            'user',
            'agency',
            'faq',
        ])->orderBy('created_at', $sort);


        /*
         * 🔍 FILTER: OUTCOME
         *
         * Examples:
         * answered
         * fallback
         * greeting
         * thanks
         * irrelevant
         * clarification
         * wrong_agency
         */
        $allowedOutcomes = [
            'answered',
            'fallback',
            'greeting',
            'thanks',
            'irrelevant',
            'clarification',
            'wrong_agency',
        ];

        if (
            $request->filled('outcome') &&
            in_array($request->outcome, $allowedOutcomes, true)
        ) {
            $query->where('outcome', $request->outcome);
        }


        /*
         * 🔍 FILTER: MATCH METHOD
         *
         * Only FAQ-answering interactions have
         * a meaningful matching method.
         */
        $allowedMatchMethods = [
            'rule',
            'ai',
            'semantic',
            'none',
        ];

        if (
            $request->filled('match_method') &&
            in_array($request->match_method, $allowedMatchMethods, true)
        ) {
            $query->where(
                'match_method',
                $request->match_method
            );
        }


        /*
         * 🔍 FILTER: DATE
         *
         * whereDate() ensures the filter only compares
         * the calendar date and ignores the time portion.
         */
        if ($request->filled('date')) {
            $date = Carbon::createFromFormat(
                'Y-m-d',
                $request->date
            );

            $query->whereBetween(
                'created_at',
                [
                    $date->copy()->startOfDay(),
                    $date->copy()->endOfDay(),
                ]
            );
        }


        /*
         * 🔍 SEARCH
         *
         * Search across:
         *
         * - Question
         * - Answer
         * - User name
         * - Agency name
         * - FAQ question
         */
        if ($request->filled('search')) {

            /*
             * trim() removes unnecessary whitespace
             * from the user's search input.
             */
            $search = trim($request->search);

            if ($search !== '') {

                $query->where(function ($q) use ($search) {

                    /*
                     * QUESTION
                     */
                    $q->where(
                        'question',
                        'LIKE',
                        "%{$search}%"
                    )

                    /*
                     * ANSWER
                     */
                    ->orWhere(
                        'answer',
                        'LIKE',
                        "%{$search}%"
                    )

                    /*
                     * USER NAME
                     */
                    ->orWhereHas('user', function ($userQuery) use ($search) {

                        $userQuery
                            ->where(
                                'first_name',
                                'LIKE',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'last_name',
                                'LIKE',
                                "%{$search}%"
                            );
                    })

                    /*
                     * AGENCY NAME
                     */
                    ->orWhereHas('agency', function ($agencyQuery) use ($search) {

                        $agencyQuery->where(
                            'agency_name',
                            'LIKE',
                            "%{$search}%"
                        );
                    })

                    /*
                     * FAQ QUESTION
                     */
                    ->orWhereHas('faq', function ($faqQuery) use ($search) {

                        $faqQuery->where(
                            'question',
                            'LIKE',
                            "%{$search}%"
                        );
                    });
                });
            }
        }


        /*
         * 📊 AVAILABLE OUTCOMES
         *
         * Used by the filter dropdown in the Blade view.
         *
         * These are defined explicitly rather than blindly
         * reading database values so the UI remains predictable.
         */
        $availableOutcomes = $allowedOutcomes;


        /*
         * 📊 AVAILABLE MATCH METHODS
         *
         * Used by the second filter dropdown.
         */
        $availableMatchMethods = $allowedMatchMethods;


        /*
         * 📅 AVAILABLE DATES
         *
         * Provides recent dates that actually contain
         * chatbot interactions.
         */
        $availableDates = ChatbotLog::selectRaw(
            'DATE(created_at) as date'
        )
            ->distinct()
            ->orderByDesc('date')
            ->limit(15)
            ->pluck('date');


        /*
         * 📊 OVERALL STATISTICS
         *
         * Compute the dashboard counters in one aggregate query
         * instead of issuing a separate COUNT query for every card.
         */
        $stats = ChatbotLog::query()
            ->selectRaw('COUNT(*) AS total_chats')
            ->selectRaw("SUM(CASE WHEN outcome = 'answered' THEN 1 ELSE 0 END) AS total_answered")
            ->selectRaw("SUM(CASE WHEN outcome = 'fallback' THEN 1 ELSE 0 END) AS total_fallback")
            ->selectRaw("SUM(CASE WHEN match_method IN ('ai', 'semantic') THEN 1 ELSE 0 END) AS total_ai")
            ->selectRaw("SUM(CASE WHEN match_method = 'rule' THEN 1 ELSE 0 END) AS total_rule")
            ->selectRaw("SUM(CASE WHEN outcome = 'irrelevant' THEN 1 ELSE 0 END) AS total_irrelevant")
            ->first();

        $totalChats = (int) ($stats->total_chats ?? 0);
        $totalAnswered = (int) ($stats->total_answered ?? 0);
        $totalFallback = (int) ($stats->total_fallback ?? 0);
        $totalSemantic = (int) ($stats->total_ai ?? 0);
        $totalRule = (int) ($stats->total_rule ?? 0);
        $totalIrrelevant = (int) ($stats->total_irrelevant ?? 0);

        $answerRate = $totalChats > 0
            ? round(($totalAnswered / $totalChats) * 100, 2)
            : 0;


        /*
         * 📄 PAGINATION
         *
         * withQueryString() preserves filters and search
         * parameters while navigating between pages.
         */
        $logs = $query
            ->paginate(10)
            ->withQueryString();


        /*
         * 📤 SEND DATA TO THE VIEW
         */
        return view('admin.chatbotlogs', compact(
            'logs',
            'availableOutcomes',
            'availableMatchMethods',
            'availableDates',
            'totalChats',
            'totalAnswered',
            'totalFallback',
            'totalSemantic',
            'totalRule',
            'totalIrrelevant',
            'answerRate'
        ));
    }
}