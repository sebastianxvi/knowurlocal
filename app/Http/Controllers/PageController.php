<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\UserLog;
use App\Models\Agency;

class PageController extends Controller
{
    public function map()
    {
        return view('public_user.map');
    }

    public function agencies()
    {
        // 🔒 Log page visit
        if (Auth::check()) {
            UserLog::create([
                'user_id' => Auth::id(),
                'action' => 'view_agencies',
                'page' => 'agencies_list',
                'ip_address' => request()->ip(),
                'device' => substr(
                    request()->userAgent(),
                    0,
                    255
                ),
                'role' => Auth::user()->role,
            ]);
        }

        // 📦 Fetch agencies from DB
        $agencies = Agency::query()
            ->select([
                'id',
                'agency_name',
                'agency_location',
                'agency_image',
                'agency_hotline',
                'agency_email',
            ])
            ->orderBy('agency_name')
            ->get();

        return view(
            'public_user.agencies',
            compact('agencies')
        );
    }
}