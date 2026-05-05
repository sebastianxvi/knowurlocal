<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminInvite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminInviteController extends Controller
{
    /**
     * =========================
     * 🔐 SEND INVITE (SUPERADMIN)
     * =========================
     */
    public function sendInvite(Request $request)
    {
        // 🔒 Validate email (must not exist yet)
        $request->validate([
            'email' => 'required|email|unique:users,email'
        ]);

        // 🔐 Generate secure token (unguessable)
        $token = Str::random(64);

        // 🧠 Store invite
        AdminInvite::create([
            'email' => $request->email,
            'token' => $token,
            'created_by' => Auth::id(),
            'expires_at' => now()->addHours(24),
            'used' => false,
        ]);

        // 📧 Build invite link
        $link = url('/admin/register?token=' . $token);

        // 📧 Send email
        Mail::raw("You’ve been invited as an admin.\n\nRegister here:\n$link", function ($message) use ($request) {
            $message->to($request->email)
                    ->subject('KNOWURLOCAL Admin Invitation');
        });

        return back()->with('success', 'Admin invite sent successfully.');
    }

    /**
     * =========================
     * 🔐 VALIDATE INVITE TOKEN
     * =========================
     */
    public function validateInvite(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $invite = AdminInvite::where('token', $request->token)->first();

        // 🔒 Check validity
        if (!$invite || !$invite->isValid()) {
            abort(403, 'Invalid or expired invite.');
        }

        return view('admin.register', [
            'email' => $invite->email,
            'token' => $invite->token
        ]);
    }
}