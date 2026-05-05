<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * 📋 DISPLAY USERS
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'user');

        /**
         * 🔍 SEARCH (SAFE)
         */
        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        /**
         * 🔽 SORT (SECURE WHITELIST)
         */
        $sort = $request->get('sort', 'desc');
        $sort = in_array($sort, ['asc', 'desc']) ? $sort : 'desc';

        $query->orderBy('created_at', $sort);

        /**
         * 📄 PAGINATION
         */
        $users = $query->paginate(10)->withQueryString();

        return view('admin.users', compact('users'));
    }

    /**
     * 🗑 DELETE USER (SUPERADMIN ONLY)
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        /**
         * 🔒 SECURITY: ONLY SUPERADMIN
         */
        if (auth()->user()->role !== 'superadmin') {
            abort(403, 'Unauthorized action.');
        }

        /**
         * 🚫 PREVENT SELF DELETE
         */
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        /**
         * 🧹 DELETE USER
         */
        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}