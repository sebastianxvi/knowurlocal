<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminInvite;
use App\Models\UserLog;
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
    // Validate that the supplied address is valid
    // and does not already belong to an existing user.
    $request->validate([
        'email' => 'required|email|unique:users,email'
    ]);

    // Generate a cryptographically random invitation token.
    $token = Str::random(64);

    // Create the invitation record.
    $invite = AdminInvite::create([
        'email' => $request->email,
        'token' => $token,
        'created_by' => Auth::id(),
        'expires_at' => now()->addHours(24),
        'used' => false,
    ]);

    // Generate the registration URL containing the invitation token.
    $link = url('/admin/register?token=' . $token);

    // Send the invitation email.
    Mail::send(
    'emails.admin-invitation',
    [
        'link' => $link,
        'expiresAt' => $invite->expires_at,
    ],
    function ($message) use ($request) {

        $message
            ->to($request->email)
            ->subject('You’re invited to KNOWURLOCAL Admin');
    }
);


    // Record the successful invitation in the audit log.
    UserLog::create([
        'user_id' => Auth::id(),

        // The invited person does not have a users.id yet.
        'target_user_id' => null,

        // Admin invitations are not associated with an agency.
        'agency_id' => null,

        'action' => 'invite_admin',
        'page' => 'admin_management',

        // Record the role of the administrator performing the action.
        'role' => Auth::user()->role,

        'ip_address' => $request->ip(),

        // Keep the browser/device information within the database limit.
        'device' => substr(
            $request->userAgent(),
            0,
            255
        ),

        // There was no previous admin account for this email.
        'old_values' => [
            'status' => 'Data did not exist',
        ],

        // Record only information relevant to the invitation.
        // Never store the invitation token here.
        'new_values' => [
            'email' => $invite->email,
            'status' => 'Invitation sent',
            'expires_at' => $invite->expires_at->toDateTimeString(),
        ],

        // Short legacy representation.
        'old_value' => 'status: Data did not exist',

        'new_value' =>
            'email: ' . $invite->email .
            ', status: Invitation sent',

        'description' =>
            'Sent admin invitation to: ' . $invite->email,
    ]);

    return back()->with(
        'success',
        'Admin invite sent successfully.'
    );
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