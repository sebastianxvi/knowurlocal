<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserLog;
use App\Models\Agency; // ✅ IMPORTANT

class PageController extends Controller
{
    public function map(Request $request)
    {
        // 🔒 Only log if user is logged in
        if (Auth::check()) {
            UserLog::create([
                'user_id' => Auth::id(),
                'action' => 'view_map',
                'page' => 'map',
                'ip_address' => $request->ip(),
                'device' => $request->userAgent(),
            ]);
        }

        return view('public_user.map');
    }

    // 🔥 ADD THIS METHOD
    public function agencies(Request $request)
    {
        // 🔒 Log page visit
        if (Auth::check()) {
            UserLog::create([
                'user_id' => Auth::id(),
                'action' => 'view_agencies',
                'page' => 'agencies_list',
                'ip_address' => $request->ip(),
                'device' => $request->userAgent(),
            ]);
        }

        // 📦 Fetch agencies from DB
        $agencies = Agency::all();

        // 🎯 Pass to view
        return view('public_user.agencies', compact('agencies'));
    }
}