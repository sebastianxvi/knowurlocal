<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AdminManagementController extends Controller
{
    /**
 * 📄 DISPLAY ADMINS
 *
 * Displays administrator accounts and provides the status
 * counts used by the status navigation tabs.
 */
public function admins(Request $request)
{

/*
     * Default Admin Management to the Active tab.
     */
    if (!$request->filled('status')) {
        $request->merge([
            'status' => 'active',
        ]);
    }
    /*
     * Define the administrator roles once.
     *
     * This prevents regular public users from appearing in
     * Admin Management and keeps all status counts scoped
     * to administrator accounts only.
     */
    $adminRoles = [
        'admin',
        'superadmin',
    ];


    /*
     * Create the base administrator query.
     *
     * We will clone this query when calculating the
     * individual status counts.
     */
    $baseQuery = User::whereIn(
        'role',
        $adminRoles
    );


    /*
     * =====================================================
     * STATUS COUNTS
     * =====================================================
     *
     * These counts are independent of search, role, and sort.
     *
     * They represent the total number of administrator
     * accounts currently in each lifecycle state.
     */

    $activeCount = (clone $baseQuery)
        ->where('status', 'active')
        ->count();


    $pendingCount = (clone $baseQuery)
        ->where('status', 'pending')
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
     * apply search, role, status, and sorting.
     */
    $query = clone $baseQuery;


    /*
     * SEARCH
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
     * ROLE FILTER
     *
     * Only allow the two administrator roles that this
     * page is designed to manage.
     */
    if ($request->filled('role')) {

        $allowedRoles = [
            'admin',
            'superadmin',
        ];

        if (
            in_array(
                $request->role,
                $allowedRoles,
                true
            )
        ) {

            $query->where(
                'role',
                $request->role
            );
        }
    }


    /*
     * STATUS FILTER
     *
     * The status is now controlled by the separate status tabs.
     *
     * We still receive it through the GET request, but it is
     * no longer represented as a dropdown inside the filter bar.
     */
    if ($request->filled('status')) {

        $allowedStatuses = [
            'pending',
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
     * SORT
     *
     * Only allow explicitly supported sort directions.
     *
     * This prevents arbitrary request values from being used
     * in the ORDER BY clause.
     */
    $sort = in_array(
        $request->get('sort'),
        ['asc', 'desc'],
        true
    )
        ? $request->sort
        : 'desc';


    /*
     * Apply the selected creation-date ordering.
     */
    $query->orderBy(
        'created_at',
        $sort
    );


    /*
     * Paginate the administrator results.
     *
     * withQueryString() preserves search, role, status,
     * and sorting when navigating between pages.
     */
    $admins = $query
        ->paginate(10)
        ->withQueryString();


    /*
     * Send both the table results and status counts to the
     * Blade view.
     */
    return view(
        'admin.admins',
        compact(
            'admins',
            'activeCount',
            'pendingCount',
            'deactivatedCount'
        )
    );
}

    /**
     * 📧 INVITE ADMIN
     */
    public function invite(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
        ]);

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make('temporary123'),
            'role' => 'admin',
            'status' => 'pending',
        ]);

        UserLog::create([
            'user_id' => auth()->id() ?? 0,
            'target_user_id' => $user->id,
            'action' => 'invite_admin',
            'page' => 'admin_management',
            'role' => auth()->user()->role ?? 'admin',
            'ip_address' => request()->ip(),
            'device' => substr(request()->userAgent(), 0, 255),

            'old_values' => [
    'status' => 'Data did not exist',
],

'new_values' => [
    'email' => $user->email,
    'role' => $user->role,
    'status' => $user->status,
],

            'old_value' => 'status: Data did not exist',

'new_value' =>
    'email: ' . $user->email .
    ', role: ' . $user->role .
    ', status: ' . $user->status,

            'description' => 'Invited admin: ' . $user->email,
        ]);

        return back()->with('success', 'Invitation sent.');
    }

 

    /**
 * ✅ APPROVE ADMIN
 */
public function approve($id)
{
    /*
     * Find the administrator being approved.
     *
     * findOrFail() automatically returns a 404 response
     * if the supplied ID does not exist.
     */
    $user = User::findOrFail($id);

    /*
     * Capture the status before changing it.
     *
     * This is used by the audit log so we can see the
     * exact state transition that occurred.
     */
    $oldValues = [
        'status' => $user->status,
    ];

    /*
     * Change the administrator's status to active.
     */
    $user->update([
        'status' => 'active',
    ]);

    /*
     * Refresh the model so we are working with the value
     * that was actually persisted to the database.
     */
    $user->refresh();

    /*
     * Capture the new status for the audit log.
     */
    $newValues = [
        'status' => $user->status,
    ];

    /*
     * Record the approval action before attempting to
     * communicate with the external mail service.
     */
    $this->logAdminAction(
        $user,
        'approve_admin',
        $oldValues,
        $newValues,
        'Approved admin: ' . $user->email
    );

    /*
     * Generate the admin login URL using Laravel's named route.
     *
     * route() is preferable to hardcoding /admin/login because
     * the URL will automatically remain correct if the route
     * definition changes later.
     */
    $loginLink = route('admin.login');

    /*
     * Send the approval notification.
     *
     * The email is intentionally handled separately from the
     * approval operation. A temporary SMTP failure should not
     * undo an already-approved administrator account.
     */
    try {

        Mail::send(
            'emails.admin-approved',
            [
                'firstName' => $user->first_name,
                'email' => $user->email,
                'loginLink' => $loginLink,
            ],
            function ($message) use ($user) {

                $message
                    ->to($user->email)
                    ->subject(
                        'Your KNOWURLOCAL Admin Account Has Been Approved'
                    );
            }
        );

    } catch (\Throwable $e) {

        /*
         * Do not expose the mail-server exception to the user.
         *
         * The approval has already succeeded, so we simply
         * record the technical failure in Laravel's log.
         */
        \Log::error(
            'ADMIN APPROVAL EMAIL FAILED',
            [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]
        );
    }

    /*
     * Return the administrator to the management page.
     */
    return back()->with(
        'success',
        'Admin approved.'
    );
}


/**
 * ⛔ DEACTIVATE ADMIN
 *
 * Disables the administrator account without deleting it.
 *
 * The account remains in the database so it can be
 * reactivated later by a Super Admin.
 */
public function deactivate($id)
{
    /*
     * Find the administrator account being deactivated.
     */
    $user = User::findOrFail($id);

    /*
     * Prevent an account that is already deactivated
     * from being processed again.
     */
    if ($user->status === 'deactivated') {

        return back()->with(
            'error',
            'This admin account is already deactivated.'
        );
    }

    /*
     * Capture the state before the change.
     */
    $oldValues = [
        'status' => $user->status,
    ];

    /*
     * Change only the account's access state.
     *
     * The role remains unchanged.
     */
    $user->update([
        'status' => 'deactivated',
    ]);

    /*
     * Refresh so the audit record reflects the actual
     * value persisted by the database.
     */
    $user->refresh();

    $newValues = [
        'status' => $user->status,
    ];

    /*
     * Record the administrative action.
     */
    $this->logAdminAction(
        $user,
        'deactivate_admin',
        $oldValues,
        $newValues,
        'Deactivated admin: ' . $user->email
    );

    return back()->with(
        'success',
        'Admin account deactivated.'
    );
}


/**
 * ♻️ REACTIVATE ADMIN
 *
 * Restores a previously deactivated administrator account
 * to the active state.
 */
public function reactivate($id)
{
    /*
     * Find the administrator account.
     */
    $user = User::findOrFail($id);

    /*
     * Prevent an already-active account from being
     * unnecessarily processed.
     */
    if ($user->status === 'active') {

        return back()->with(
            'error',
            'This admin account is already active.'
        );
    }

    /*
     * Capture the state before reactivation.
     */
    $oldValues = [
        'status' => $user->status,
    ];

    /*
     * Restore the account's access.
     */
    $user->update([
        'status' => 'active',
    ]);

    /*
     * Confirm the value actually persisted.
     */
    $user->refresh();

    $newValues = [
        'status' => $user->status,
    ];

    /*
     * Record the administrative action.
     */
    $this->logAdminAction(
        $user,
        'reactivate_admin',
        $oldValues,
        $newValues,
        'Reactivated admin: ' . $user->email
    );

    return back()->with(
        'success',
        'Admin account reactivated.'
    );
}



    /**
 * ⬆ PROMOTE ADMIN
 */
public function promote($id)
{
    /*
     * Find the administrator being promoted.
     */
    $user = User::findOrFail($id);

    /*
     * Capture the role before the change.
     */
    $oldValues = [
        'role' => $user->role,
    ];

    /*
     * Promote the administrator.
     */
    $user->update([
        'role' => 'superadmin',
    ]);

    /*
     * Refresh to obtain the actual database value.
     */
    $user->refresh();

    $newValues = [
        'role' => $user->role,
    ];

    /*
     * Record the role transition.
     */
    $this->logAdminAction(
        $user,
        'promote_admin',
        $oldValues,
        $newValues,
        'Promoted admin: ' . $user->email
    );

    return back()->with(
        'success',
        'Admin promoted.'
    );
}



    /**
 * ⬇ DEMOTE ADMIN
 */
public function demote($id)
{
    /*
     * Find the administrator being demoted.
     */
    $user = User::findOrFail($id);

    /*
     * Capture the current role.
     */
    $oldValues = [
        'role' => $user->role,
    ];

    /*
     * Demote the account to normal admin.
     */
    $user->update([
        'role' => 'admin',
    ]);

    /*
     * Refresh to confirm the persisted value.
     */
    $user->refresh();

    $newValues = [
        'role' => $user->role,
    ];

    /*
     * Record the role transition.
     */
    $this->logAdminAction(
        $user,
        'demote_admin',
        $oldValues,
        $newValues,
        'Demoted admin: ' . $user->email
    );

    return back()->with(
        'success',
        'Admin demoted.'
    );
}


    /**
 * ❌ DELETE ADMIN
 */
public function delete($id)
{
    /*
     * Find the administrator before deletion.
     */
    $user = User::findOrFail($id);

    /*
     * Capture useful, non-sensitive information.
     *
     * IMPORTANT:
     * Never put password/password_hash into an audit log.
     */
    $oldValues = [
        'first_name' => $user->first_name,
        'last_name'  => $user->last_name,
        'email'      => $user->email,
        'role'       => $user->role,
        'status'     => $user->status,
    ];

    /*
     * Perform the existing deletion behavior.
     *
     * If User uses SoftDeletes, this will be recoverable.
     * If it does not, this remains a permanent deletion.
     */
    $user->delete();

    /*
     * We intentionally do NOT store NULL here.
     *
     * The new state is represented explicitly as an audit event.
     */
    $newValues = [
        'status' => 'Data deleted',
    ];

    /*
     * Record the deletion.
     */
    $this->logAdminAction(
        $user,
        'delete_admin',
        $oldValues,
        $newValues,
        'Deleted admin: ' . $user->email
    );

    return back()->with(
        'success',
        'Admin deleted.'
    );
}



    /**
 * 🔒 CENTRALIZED ADMIN AUDIT LOGGING
 *
 * Keeps all admin-management audit records consistent.
 *
 * Only safe audit fields should be passed here.
 * Never pass passwords, password hashes, tokens, or other
 * sensitive authentication data.
 */
private function logAdminAction(
    User $user,
    string $action,
    array $oldValues,
    array $newValues,
    string $description
): void {
    UserLog::create([
        /*
         * The currently authenticated administrator is the actor.
         */
        'user_id' => auth()->id(),

        /*
         * The administrator account being affected.
         */
        'target_user_id' => $user->id,

        /*
         * This is an admin-management audit, so there is
         * no agency associated with the action.
         */
        'agency_id' => null,

        'action' => $action,

        'page' => 'admin_management',

        /*
         * Store the actor's role at the time of the action.
         */
        'role' => auth()->user()->role,

        'ip_address' => request()->ip(),

        /*
         * Limit the user-agent length so it fits safely
         * within the database column.
         */
        'device' => substr(
            request()->userAgent(),
            0,
            255
        ),

        /*
         * UserLog casts these JSON columns to arrays.
         *
         * Therefore we pass arrays directly instead of
         * manually calling json_encode().
         */
        'old_values' => $oldValues,
        'new_values' => $newValues,

        /*
         * Keep the legacy columns populated as well.
         *
         * These are only short summaries. The complete
         * structured audit data lives in old_values/new_values.
         */
        'old_value' => !empty($oldValues)
            ? collect($oldValues)
                ->map(
                    fn ($value, $key) =>
                        $key . ': ' . (is_scalar($value)
                            ? $value
                            : json_encode($value))
                )
                ->implode(', ')
            : null,

        'new_value' => !empty($newValues)
            ? collect($newValues)
                ->map(
                    fn ($value, $key) =>
                        $key . ': ' . (is_scalar($value)
                            ? $value
                            : json_encode($value))
                )
                ->implode(', ')
            : null,

        'description' => $description,
    ]);
}
}