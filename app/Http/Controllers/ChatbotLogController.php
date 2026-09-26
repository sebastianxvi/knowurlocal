<?php

namespace App\Http\Controllers;

use App\Models\ChatbotLog;
use Illuminate\Http\Request;

class ChatbotLogController extends Controller
{
    /**
     * Display chatbot interaction logs.
     */
    public function index(Request $request)
    {
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
            $query->whereDate(
                'created_at',
                $request->date
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
         * These statistics are based on the actual
         * chatbot interaction records.
         */
        $totalChats = ChatbotLog::count();

        $totalAnswered = ChatbotLog::where(
            'outcome',
            'answered'
        )->count();

        $totalFallback = ChatbotLog::where(
            'outcome',
            'fallback'
        )->count();

        $totalSemantic = ChatbotLog::where(
            'match_method',
            'semantic'
        )->count();

        $totalRule = ChatbotLog::where(
            'match_method',
            'rule'
        )->count();

        $totalIrrelevant = ChatbotLog::where(
            'outcome',
            'irrelevant'
        )->count();


        /*
         * 📈 ANSWER RATE
         *
         * Avoid division by zero when there are no logs.
         */
        $answerRate = $totalChats > 0
            ? round(
                ($totalAnswered / $totalChats) * 100,
                2
            )
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