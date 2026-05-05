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

            'old_values' => null,
            'new_values' => ['email' => $user->email],

            'old_value' => null,
            'new_value' => 'email: ' . $user->email,

            'description' => 'Invited admin: ' . $user->email,
        ]);

        return back()->with('success', 'Invitation sent.');
    }

    /**
     * ✅ APPROVE ADMIN
     */
    public function approve($id)
    {
        $user = User::findOrFail($id);

        // 🔥 CAPTURE OLD BEFORE UPDATE
        $old = [
            'status' => $user->getOriginal('status')
        ];

        $user->update([
            'status' => 'active'
        ]);

        // 🔥 NEW AFTER UPDATE
        $new = [
            'status' => $user->fresh()->status
        ];

        UserLog::create([
            'user_id' => auth()->id() ?? 0,
            'target_user_id' => $user->id,
            'action' => 'approve_admin',
            'page' => 'admin_management',
            'role' => auth()->user()->role ?? 'admin',
            'ip_address' => request()->ip(),
            'device' => substr(request()->userAgent(), 0, 255),

            'old_values' => $old,
            'new_values' => $new,

            'old_value' => 'status: ' . $old['status'],
            'new_value' => 'status: ' . $new['status'],

            'description' => 'Approved admin: ' . $user->email,
        ]);

        return back()->with('success', 'Admin approved.');
    }

    /**
     * ⬆ PROMOTE ADMIN
     */
    public function promote($id)
    {
        $user = User::findOrFail($id);

        $old = [
            'role' => $user->getOriginal('role')
        ];

        $user->update([
            'role' => 'superadmin'
        ]);

        $new = [
            'role' => $user->fresh()->role
        ];

        UserLog::create([
            'user_id' => auth()->id() ?? 0,
            'target_user_id' => $user->id,
            'action' => 'promote_admin',
            'page' => 'admin_management',
            'role' => auth()->user()->role ?? 'admin',
            'ip_address' => request()->ip(),
            'device' => substr(request()->userAgent(), 0, 255),

            'old_values' => $old,
            'new_values' => $new,

            'old_value' => 'role: ' . $old['role'],
            'new_value' => 'role: ' . $new['role'],

            'description' => 'Promoted admin: ' . $user->email,
        ]);

        return back()->with('success', 'Admin promoted.');
    }

    /**
     * ⬇ DEMOTE ADMIN
     */
    public function demote($id)
    {
        $user = User::findOrFail($id);

        $old = [
            'role' => $user->getOriginal('role')
        ];

        $user->update([
            'role' => 'admin'
        ]);

        $new = [
            'role' => $user->fresh()->role
        ];

        UserLog::create([
            'user_id' => auth()->id() ?? 0,
            'target_user_id' => $user->id,
            'action' => 'demote_admin',
            'page' => 'admin_management',
            'role' => auth()->user()->role ?? 'admin',
            'ip_address' => request()->ip(),
            'device' => substr(request()->userAgent(), 0, 255),

            'old_values' => $old,
            'new_values' => $new,

            'old_value' => 'role: ' . $old['role'],
            'new_value' => 'role: ' . $new['role'],

            'description' => 'Demoted admin: ' . $user->email,
        ]);

        return back()->with('success', 'Admin demoted.');
    }

    /**
     * ❌ DELETE ADMIN
     */
    public function delete($id)
    {
        $user = User::findOrFail($id);

        $old = [
            'email' => $user->email
        ];

        $user->delete();

        UserLog::create([
            'user_id' => auth()->id() ?? 0,
            'target_user_id' => $user->id,
            'action' => 'delete_admin',
            'page' => 'admin_management',
            'role' => auth()->user()->role ?? 'admin',
            'ip_address' => request()->ip(),
            'device' => substr(request()->userAgent(), 0, 255),

            'old_values' => $old,
            'new_values' => null,

            'old_value' => 'email: ' . $old['email'],
            'new_value' => null,

            'description' => 'Deleted admin: ' . $old['email'],
        ]);

        return back()->with('success', 'Admin deleted.');
    }
}