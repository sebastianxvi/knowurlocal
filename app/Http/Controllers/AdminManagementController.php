<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserLog;
use Illuminate\Support\Facades\Hash;

class AdminManagementController extends Controller
{
    /**
     * 📄 DISPLAY ADMINS
     */
    public function admins(Request $request)
    {
        $query = User::whereIn('role', ['admin', 'superadmin']);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sort = in_array($request->get('sort'), ['asc','desc'])
            ? $request->sort
            : 'desc';

        $query->orderBy('created_at', $sort);

        $admins = $query->paginate(10)->withQueryString();

        return view('admin.admins', compact('admins'));
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
     */
    $user = User::findOrFail($id);

    /*
     * Capture ONLY the value relevant to this action.
     *
     * We do not need to record email, password, name, etc.
     */
    $oldValues = [
        'status' => $user->status,
    ];

    /*
     * Perform the actual business operation.
     */
    $user->update([
        'status' => 'active',
    ]);

    /*
     * Read the value that was actually persisted.
     */
    $user->refresh();

    $newValues = [
        'status' => $user->status,
    ];

    /*
     * Record the audit event.
     */
    $this->logAdminAction(
        $user,
        'approve_admin',
        $oldValues,
        $newValues,
        'Approved admin: ' . $user->email
    );

    return back()->with(
        'success',
        'Admin approved.'
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