<?php

namespace App\Http\Controllers;

use App\Models\SupportRequest;
use App\Models\User;
use App\Models\UserLog;
use Illuminate\Http\Request;

class UserController extends Controller
{
    
/**
 * 📋 DISPLAY PUBLIC USERS
 *
 * Displays public-user accounts and provides the status
 * counts used by the status navigation tabs.
 */
public function index(Request $request)
{
    /*
     * Default User Management to the Active tab.
     *
     * This means /admin/users will show active users
     * unless another valid status is explicitly selected.
     */
    if (!$request->filled('status')) {
        $request->merge([
            'status' => 'active',
        ]);
    }


    /*
     * Create the base public-user query.
     *
     * Only accounts with the "user" role belong in this
     * management page.
     */
    $baseQuery = User::where(
        'role',
        'user'
    );


    /*
     * =====================================================
     * STATUS COUNTS
     * =====================================================
     *
     * These counts are independent of search and sorting.
     *
     * They represent the total number of public users
     * currently in each account state.
     */
    $activeCount = (clone $baseQuery)
        ->where('status', 'active')
        ->count();


    $deactivatedCount = (clone $baseQuery)
        ->where('status', 'deactivated')
        ->count();


    /*
     * =====================================================
     * TABLE QUERY
     * =====================================================
     *
     * Clone the base query so the table can independently
     * apply search, status, and sorting.
     */
    $query = clone $baseQuery;


    /*
     * =====================================================
     * SEARCH
     * =====================================================
     *
     * Search by first name, last name, or email.
     */
    if ($request->filled('search')) {

        $search = $request->search;

        $query->where(function ($q) use ($search) {

            $q->where(
                'first_name',
                'LIKE',
                "%{$search}%"
            )

            ->orWhere(
                'last_name',
                'LIKE',
                "%{$search}%"
            )

            ->orWhere(
                'email',
                'LIKE',
                "%{$search}%"
            );
        });
    }


    /*
     * =====================================================
     * STATUS FILTER
     * =====================================================
     *
     * Status is controlled by the separate status tabs
     * above the filter bar.
     *
     * Only explicitly supported statuses are accepted.
     */
    if ($request->filled('status')) {

        $allowedStatuses = [
            'active',
            'deactivated',
        ];

        if (
            in_array(
                $request->status,
                $allowedStatuses,
                true
            )
        ) {

            $query->where(
                'status',
                $request->status
            );
        }
    }


    /*
     * =====================================================
     * SORT
     * =====================================================
     *
     * Only allow known sort directions.
     *
     * This prevents arbitrary request values from being
     * passed into the ORDER BY clause.
     */
    $sort = in_array(
        $request->get('sort'),
        ['asc', 'desc'],
        true
    )
        ? $request->sort
        : 'desc';


    /*
     * Apply the selected registration-date ordering.
     */
    $query->orderBy(
        'created_at',
        $sort
    );


    /*
     * =====================================================
     * PAGINATION
     * =====================================================
     *
     * withQueryString() preserves:
     *
     * - search
     * - status
     * - sort
     *
     * when navigating between pages.
     */
    $users = $query
        ->paginate(10)
        ->withQueryString();


    /*
     * Send both the users and status counts to the Blade.
     */
    return view(
        'admin.users',
        compact(
            'users',
            'activeCount',
            'deactivatedCount'
        )
    );
}
    
/**
 * ⛔ DEACTIVATE PUBLIC USER
 *
 * Deactivates the account without deleting it.
 *
 * The user remains in the database and can be
 * reactivated later.
 */
public function deactivate($id)
{
    /*
     * Only find accounts that belong to public users.
     *
     * This prevents this method from accidentally
     * modifying an administrator account.
     */
    $user = User::where('role', 'user')
        ->findOrFail($id);


    /*
     * Prevent the same account from being deactivated
     * repeatedly.
     */
    if ($user->status === 'deactivated') {

        return back()->with(
            'error',
            'This user account is already deactivated.'
        );
    }


    /*
    /*
 * Store the state before changing it.
 *
 * This becomes the "old" side of the audit record.
 */
$oldValues = [
    'status' => $user->status,
];


/*
 * Change the account status.
 */
$user->update([
    'status' => 'deactivated',
]);


/*
 * Record the successful administrative action.
 *
 * target_user_id identifies the affected public user.
 *
 * old_values/new_values preserve exactly what changed.
 */
UserLog::create([
    'user_id' => auth()->id(),

    'target_user_id' => $user->id,

    'action' => 'deactivate_user',

    'page' => 'admin_users',

    'role' => auth()->user()->role,

    'ip_address' => request()->ip(),

    'device' => request()->userAgent(),

    'old_values' => $oldValues,

    'new_values' => [
        'status' => $user->status,
    ],

    'description' =>
        'Deactivated public user account.',
]);


return back()->with(
    'success',
    'User account deactivated.'
);
}


/**
 * ♻️ REACTIVATE PUBLIC USER
 *
 * Restores a previously deactivated account.
 */
public function reactivate($id)
{
    /*
     * Only find accounts belonging to public users.
     */
    $user = User::where('role', 'user')
        ->findOrFail($id);


    /*
     * Prevent unnecessary reactivation.
     */
    if ($user->status === 'active') {

        return back()->with(
            'error',
            'This user account is already active.'
        );
    }


    /*
 * Capture the state before reactivation.
 */
$oldValues = [
    'status' => $user->status,
];


/*
 * Restore the account.
 */
$user->update([
    'status' => 'active',
]);


/*
 * Record the successful action.
 */
UserLog::create([
    'user_id' => auth()->id(),

    'target_user_id' => $user->id,

    'action' => 'reactivate_user',

    'page' => 'admin_users',

    'role' => auth()->user()->role,

    'ip_address' => request()->ip(),

    'device' => request()->userAgent(),

    'old_values' => $oldValues,

    'new_values' => [
        'status' => $user->status,
    ],

    'description' =>
        'Reactivated public user account.',
]);


return back()->with(
    'success',
    'User account reactivated.'
);
}


/**
 * 🗑 DELETE USER (SUPERADMIN ONLY)
 *
 * Permanently removes a public-user account.
 */
public function destroy($id)
{
    /*
     * Only find accounts belonging to public users.
     */
    $user = User::where('role', 'user')
        ->findOrFail($id);


    /*
     * Defense-in-depth authorization check.
     *
     * The route is already Superadmin-only, but we
     * enforce the rule here as well.
     */
    if (auth()->user()->role !== 'superadmin') {

        abort(
            403,
            'Unauthorized action.'
        );
    }


    /*
     * Prevent deleting the currently authenticated user.
     */
    if ($user->id === auth()->id()) {

        return back()->with(
            'error',
            'You cannot delete your own account.'
        );
    }


    /*
 * Capture the complete relevant user state BEFORE deletion.
 *
 * This snapshot is what allows the audit log to remain
 * meaningful even after the user record no longer exists.
 */
$oldValues = [
    'first_name' => $user->first_name,

    'last_name' => $user->last_name,

    'email' => $user->email,

    'role' => $user->role,

    'status' => $user->status,
];


/*
 * Create the audit record BEFORE deleting the user.
 *
 * target_user_id preserves the original user ID.
 *
 * old_values preserves the historical identity and state.
 */
UserLog::create([
    'user_id' => auth()->id(),

    'target_user_id' => $user->id,

    'action' => 'delete_user',

    'page' => 'admin_users',

    'role' => auth()->user()->role,

    'ip_address' => request()->ip(),

    'device' => request()->userAgent(),

    'old_values' => $oldValues,

    'new_values' => [],

    'description' =>
        'Permanently deleted public user account.',
]);


/*
 * Only delete after the audit record has been
 * successfully created.
 */
$user->delete();


return back()->with(
    'success',
    'User permanently deleted.'
);
}



    public function inquiries($id)
{
    $user = User::findOrFail($id);

    $logs = SupportRequest::where('user_id', $id)
        ->latest()
        ->get();

    return response()->json([
        'logs' => $logs
    ]);
}
}