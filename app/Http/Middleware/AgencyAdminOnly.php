<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AgencyAdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'agency_admin' || !$user->agency_id) {
            abort(403, 'Unauthorized access.');
        }

        // Do not allow an account assigned to a deleted agency to keep
        // using the agency workspace.
        if (!$user->agency()->whereNull('deleted_at')->exists()) {
            abort(403, 'The assigned agency is unavailable.');
        }

        return $next($request);
    }
}
