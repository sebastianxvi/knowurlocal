<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminInvite;
use App\Models\Agency;
use App\Models\UserLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
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
    // Normalize the recipient before validation and persistence so
    // invitations use the same canonical email representation as auth.
    $request->merge([
        'email' => strtolower(trim((string) $request->input('email', ''))),
    ]);

    // Validate that the supplied address is valid
    // and does not already belong to an existing user.
    $request->validate([
        'email' => 'required|email|max:255|unique:users,email'
    ]);

    // Generate a cryptographically random invitation token.
    $token = Str::random(64);

    // Create the invitation record.
    $invite = AdminInvite::create([
        'email' => $request->email,
        'role' => 'admin',
        'agency_id' => null,
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

        // Validate the invitation and its agency before rendering registration.
        // A soft-deleted agency must not receive an invitation that can never
        // complete OTP verification.
        if (!$invite || !$invite->isValid()) {
            abort(403, 'Invalid or expired invite.');
        }

        $role = $invite->role ?: 'admin';
        $agency = null;

        if ($role === 'agency_admin') {
            $agency = Agency::query()->whereKey($invite->agency_id)->first();

            if (!$invite->agency_id || !$agency || $agency->trashed()) {
                abort(403, 'This Agency Admin invitation is no longer valid. Please contact the system administrator.');
            }

            // Do not let a stale invitation proceed if the agency already
            // acquired an active administrator after the invitation was sent.
            $alreadyAssigned = \App\Models\User::query()
                ->where('agency_id', $agency->id)
                ->where('role', 'agency_admin')
                ->where('status', 'active')
                ->exists();

            if ($alreadyAssigned) {
                abort(403, 'This agency already has an active Agency Admin. Please contact the system administrator.');
            }
        }

        return view('admin.register', [
            'email' => strtolower(trim($invite->email)),
            'token' => $invite->token,
            'role' => $role,
            'agency' => $agency,
        ]);
    }

    /**
     * Show the superadmin-only Agency Admin invitation workspace.
     */
    public function agencyAdmins(Request $request)
    {
        abort_unless(auth()->check() && in_array(auth()->user()->role, ['admin', 'superadmin'], true), 403);

        $status = $request->query('status', 'active');
        if (!in_array($status, ['active', 'pending', 'deactivated'], true)) {
            $status = 'active';
        }

        $agencies = Agency::query()
            ->whereNull('deleted_at')
            ->orderBy('agency_name')
            ->get();

        $baseAdmins = \App\Models\User::query()
            ->with('agency')
            ->where('role', 'agency_admin');

        $activeCount = (clone $baseAdmins)->where('status', 'active')->count();
        $deactivatedCount = (clone $baseAdmins)->where('status', 'deactivated')->count();

        $pendingInvitesQuery = AdminInvite::query()
            ->with(['agency', 'creator'])
            ->where('role', 'agency_admin')
            ->where('used', false)
            ->where('expires_at', '>', now());

        $pendingCount = (clone $pendingInvitesQuery)->count();

        $agencyAdmins = (clone $baseAdmins)
            ->when($status !== 'pending', fn ($query) => $query->where('status', $status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->query('search'));
                $query->where(function ($nested) use ($search) {
                    $nested->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('agency_id')
            ->orderBy('first_name')
            ->get();

        $pendingInvites = $status === 'pending'
            ? $pendingInvitesQuery->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->query('search'));
                $query->where('email', 'like', "%{$search}%");
            })->latest()->get()
            : collect();

        return view('admin.agency-admins', compact(
            'agencies', 'agencyAdmins', 'pendingInvites',
            'status', 'activeCount', 'pendingCount', 'deactivatedCount'
        ));
    }

    /**
     * Issue a verified, agency-scoped invitation. One active or pending
     * Agency Admin is allowed per agency to match the agreed operating model.
     */
    public function sendAgencyInvite(Request $request)
    {
        abort_unless(auth()->check() && in_array(auth()->user()->role, ['admin', 'superadmin'], true), 403);

        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'agency_id' => ['required', 'integer', 'exists:agencies,id'],
        ]);

        try {
            [$agency, $invite] = DB::transaction(function () use ($validated) {
                // Serialize invitations for the same agency to avoid two
                // administrators being invited concurrently.
                $agency = Agency::query()
                    ->whereKey($validated['agency_id'])
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if (!$agency || $agency->trashed()) {
                    throw new \RuntimeException('agency_unavailable');
                }

                $alreadyAssigned = \App\Models\User::query()
                    ->where('agency_id', $agency->id)
                    ->where('role', 'agency_admin')
                    ->where('status', 'active')
                    ->exists();

                if ($alreadyAssigned) {
                    throw new \RuntimeException('agency_admin_exists');
                }

                // Prevent one recipient from accumulating multiple live invite
                // links, which could otherwise conflict with the email-keyed OTP
                // verification record used during registration.
                $recipientInvitePending = AdminInvite::query()
                    ->whereRaw('LOWER(email) = ?', [strtolower($validated['email'])])
                    ->where('used', false)
                    ->where('expires_at', '>', now())
                    ->exists();

                if ($recipientInvitePending) {
                    throw new \RuntimeException('recipient_invite_pending');
                }

                $pendingInvite = AdminInvite::query()
                    ->where('agency_id', $agency->id)
                    ->where('role', 'agency_admin')
                    ->where('used', false)
                    ->where('expires_at', '>', now())
                    ->exists();

                if ($pendingInvite) {
                    throw new \RuntimeException('agency_invite_pending');
                }

                $invite = AdminInvite::create([
                    'email' => $validated['email'],
                    'role' => 'agency_admin',
                    'agency_id' => $agency->id,
                    'token' => \Illuminate\Support\Str::random(64),
                    'created_by' => auth()->id(),
                    'expires_at' => now()->addHours(24),
                    'used' => false,
                ]);

                return [$agency, $invite];
            });
        } catch (\RuntimeException $exception) {
            $field = match ($exception->getMessage()) {
                'agency_admin_exists' => 'This agency already has an active Agency Admin. Deactivate or replace the existing administrator first.',
                'recipient_invite_pending' => 'This email address already has a pending invitation. Use the existing invitation or wait for it to expire.',
                'agency_invite_pending' => 'This agency already has a pending Agency Admin invitation. Let it expire before sending another.',
                'agency_unavailable' => 'This agency is no longer available. Refresh the page and select an active agency.',
                default => 'The invitation could not be created. Please refresh the page and try again.',
            };

            return back()->withErrors(['agency_id' => $field])->withInput();
        }

        $link = route('admin.register.page', ['token' => $invite->token]);

        try {
            Mail::send('emails.admin-invitation', [
                'link' => $link,
                'expiresAt' => $invite->expires_at,
                'roleLabel' => 'Agency Administrator',
                'agencyName' => $agency->agency_name,
            ], function ($message) use ($invite, $agency) {
                $message->to($invite->email)
                    ->subject('KNOWURLOCAL | Agency Admin invitation — ' . $agency->agency_name);
            });
        } catch (\Throwable $exception) {
            // Never leave an invitation usable if its delivery failed.
            $invite->delete();
            report($exception);

            return back()->withErrors([
                'email' => 'The invitation email could not be sent. Please check mail configuration and try again.',
            ])->withInput();
        }

        app(\App\Services\AuditLogService::class)->record(
            action: 'invite_agency_admin',
            page: 'agency_admin_management',
            targetType: 'agency',
            targetId: (int) $agency->id,
            newValues: [
                'email' => $invite->email,
                'role' => 'agency_admin',
                'agency_id' => $agency->id,
                'expires_at' => $invite->expires_at->toDateTimeString(),
            ],
            description: 'Invited Agency Admin for ' . $agency->agency_name,
        );

        return back()->with('success', 'Agency Admin invitation sent. The invitation expires in 24 hours.');
    }
}