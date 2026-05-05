<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatbotLog;

class ChatbotLogController extends Controller
{


    public function index(Request $request)
    {
        /**
         * 🔒 SORT (SECURE)
         */
        $sort = $request->get('sort', 'desc');
        $sort = in_array($sort, ['asc','desc']) ? $sort : 'desc';

        /**
         * 🔧 BASE QUERY
         */
        $query = ChatbotLog::with(['agency','user'])
            ->orderBy('created_at', $sort);

        /**
         * 🔍 FILTER: TYPE
         */
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        /**
         * 🔍 FILTER: DATE
         */
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        /**
         * 🔍 SEARCH (QUESTION / ANSWER)
         */
        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function($q) use ($search) {

                // 🔹 QUESTION + ANSWER
                $q->where('question', 'LIKE', "%{$search}%")
                ->orWhere('answer', 'LIKE', "%{$search}%");

                // 🔹 USER NAME
                $q->orWhereHas('user', function($userQuery) use ($search) {
                    $userQuery->where('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%");
                });

                // 🔹 AGENCY NAME (OPTIONAL BUT GOOD)
                $q->orWhereHas('agency', function($agencyQuery) use ($search) {
                    $agencyQuery->where('agency_name', 'LIKE', "%{$search}%");
                });

            });
        }

        /**
         * 📊 TYPES (for dropdown)
         */
        $availableTypes = ChatbotLog::select('type')
            ->distinct()
            ->pluck('type');

        /**
         * 📅 DATES
         */
        $availableDates = ChatbotLog::selectRaw('DATE(created_at) as date')
            ->distinct()
            ->orderBy('date','desc')
            ->limit(15)
            ->pluck('date');

        /**
         * 📊 STATS
         */
        $totalChats = ChatbotLog::count();
        $totalFaq = ChatbotLog::where('type','faq')->count();
        $totalFallback = ChatbotLog::where('type','fallback')->count();

        /**
         * 📄 PAGINATION
         */
        $logs = $query->paginate(10)->withQueryString();

        return view('admin.chatbotlogs', compact(
            'logs',
            'availableTypes',
            'availableDates',
            'totalChats',
            'totalFaq',
            'totalFallback'
        ));
    }
}
