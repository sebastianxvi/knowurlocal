<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserLog;

class UserLogController extends Controller
{
    public function index(Request $request)
    {
        /**
         * 🔒 SORT (SECURE WHITELIST)
         */
        $sort = $request->get('sort', 'desc');
        $sort = in_array($sort, ['asc', 'desc']) ? $sort : 'desc';

        /**
         * 🔥 DEFINE ADMIN ACTIONS (SOURCE OF TRUTH)
         */
        $adminActions = [
            'admin_login',
            'admin_logout',

            'create_agency',
            'update_agency',
            'delete_agency',

            'create_faq',
            'update_faq',
            'delete_faq',

            'approve_admin',
            'invite_admin',
            'promote_admin',
            'demote_admin',
            'delete_admin'
        ];

        /**
         * 🔧 BASE QUERY
         */
        $query = UserLog::with(['agency','user','targetUser'])
            ->orderBy('created_at', $sort);

        /**
         * 🔐 ROLE-BASED DATA ACCESS
         */
        $user = auth()->user();

        if ($user->role === 'admin') {

            $query->where(function ($q) use ($user, $adminActions) {

                // ✅ Own logs (always allowed)
                $q->where('user_id', $user->id)

                // ✅ Logs from NORMAL USERS ONLY
                ->orWhere(function ($sub) use ($adminActions) {
                    $sub->whereHas('user', function ($userQuery) {
                        $userQuery->where('role', 'user'); // 🔥 ONLY normal users
                    })
                    ->whereNotIn('action', $adminActions); // still exclude admin management actions
                });

            });
        }

        $baseQuery = clone $query;

        /**
         * 🔍 FILTER: ROLE (ADMIN | USER) → BASED ON ACTION TYPE
         */
        if ($request->role === 'admin') {
            $query->whereHas('user', function ($q) {
                $q->whereIn('role', ['admin', 'superadmin']);
            });
        }

        if ($request->role === 'user') {
            $query->whereHas('user', function ($q) {
                $q->where('role', 'user');
            });
        }

        

        /**
         * 🔍 FILTER: ACTION
         */
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        /**
         * 🔍 FILTER: DATE
         */
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        /**
         * 🔍 SEARCH (MULTI-FIELD)
         */
        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function($q) use ($search) {

                $q->where('action', 'LIKE', "%{$search}%")
                  ->orWhere('page', 'LIKE', "%{$search}%")

                  ->orWhereHas('user', function($userQuery) use ($search) {
                        $userQuery->where('first_name', 'LIKE', "%{$search}%")
                                  ->orWhere('last_name', 'LIKE', "%{$search}%");
                  })

                  ->orWhereHas('agency', function($agencyQuery) use ($search) {
                        $agencyQuery->where('agency_name', 'LIKE', "%{$search}%");
                  });
            });
        }

        /**
         * 🔧 AVAILABLE ACTIONS (BASED ON CURRENT FILTER)
         */
        $availableActions = (clone $baseQuery)

    ->when($request->role === 'admin', function ($q) {
        $q->whereHas('user', function ($u) {
            $u->whereIn('role', ['admin', 'superadmin']);
        });
    })

    ->when($request->role === 'user', function ($q) use ($adminActions) {
        $q->whereHas('user', function ($u) {
            $u->where('role', 'user');
        })
        ->whereNotIn('action', $adminActions); // 🔥 NOW THIS WORKS
    })

    ->select('action')
    ->distinct()
    ->orderBy('action')
    ->pluck('action');

        /**
         * ✅ PAGINATION
         */
        $logs = $query->paginate(10)->withQueryString();

        /**
         * 📊 STATS
         */
        $totalLogs = UserLog::count();
        $totalNavigation = UserLog::where('action', 'navigate')->count();
        $totalAgencyViews = UserLog::where('action', 'view_agency')->count();

        /**
         * 📅 AVAILABLE DATES
         */
        $availableDates = (clone $baseQuery)

    ->when($request->role === 'admin', function ($q) use ($user) {

        if ($user->role === 'admin') {
            // regular admin → only own logs
            $q->where('user_id', $user->id);
        } else {
            // superadmin → all admin logs
            $q->whereHas('user', function ($u) {
                $u->whereIn('role', ['admin', 'superadmin']);
            });
        }

    })

    ->when($request->role === 'user', function ($q) use ($adminActions) {
        $q->whereHas('user', function ($u) {
            $u->where('role', 'user');
        })
        ->whereNotIn('action', $adminActions); // 🔥 prevent admin leakage
    })

    ->selectRaw('DATE(created_at) as date')
    ->distinct()
    ->orderBy('date', 'desc')
    ->limit(15)
    ->pluck('date');

        /**
         * 🚀 RETURN VIEW
         */
        return view('admin.logs', compact(
            'logs',
            'availableDates',
            'availableActions',
            'totalLogs',
            'totalNavigation',
            'totalAgencyViews'
        ));
    }
}