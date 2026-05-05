<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {

            // 🔥 admin routes → admin login
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            // 🔥 public routes → user login
            return route('public.login');
        }
    }
}