<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperAdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        // 🔐 Get currently authenticated user
        $user = auth()->user();

        /**
         * 🔒 SECURITY CHECK
         *
         * 1. Ensure user exists (prevents null errors)
         * 2. Ensure role is EXACTLY superadmin
         */
        if (!$user || $user->role !== 'superadmin') {

            // ❌ BLOCK ACCESS
            abort(403, 'Superadmin access only');
        }

        /**
         * ✅ PASS REQUEST
         */
        return $next($request);
    }
}