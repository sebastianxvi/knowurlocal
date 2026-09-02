<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Models\EmailVerification;
use App\Models\User;
use App\Models\UserLog;
use App\Models\AdminInvite;
use App\Models\PasswordResetOtp;

class AuthController extends Controller
{
    /**
     * =========================
     * 🔐 REGISTER (OTP SEND)
     * =========================
     */
    public function register(Request $request)
    {
        // 🔍 Detect admin registration via token
        $isAdminRegister = $request->has('token') && !empty($request->token);

        /**
         * 🔒 VALIDATION
         */
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|min:8|confirmed',
        ];

        if ($isAdminRegister) {
            $rules['token'] = 'required|string';
        }

        $request->validate($rules);

        // 🔐 Generate OTP
        $otp = random_int(100000, 999999);

        /**
         * =========================
         * 👑 ADMIN FLOW
         * =========================
         */
        if ($isAdminRegister) {

            $invite = AdminInvite::where('token', $request->token)->first();

            // 🔒 Validate invite
            if (!$invite || !$invite->isValid()) {
                return back()->withErrors(['email' => 'Invalid or expired invite']);
            }

            // 🔒 Ensure email matches invite
            if ($invite->email !== $request->email) {
                return back()->withErrors(['email' => 'This email is not invited']);
            }

            /**
             * 🔥 CLEAN INSERT (NO updateOrCreate)
             */
            EmailVerification::where('email', $request->email)->delete();

            EmailVerification::create([
                'email'         => $request->email,
                'first_name'    => $request->first_name,
                'last_name'     => $request->last_name,
                'password'      => Hash::make($request->password),
                'otp'           => $otp,
                'expires_at'    => now()->addMinutes(10),

                'role'          => 'admin',
                'invite_token'  => $request->token,
            ]);

            // 📧 Send OTP
            Mail::send(
    'emails.otp-verification',
    [
        'otp' => $otp,
        'isAdmin' => true,
    ],
    function ($message) use ($request) {

        $message
            ->to($request->email)
            ->subject('KNOWURLOCAL | Email Verification');
    }
);

            return redirect('/otp?email=' . urlencode($request->email));
        }

        /**
         * =========================
         * 👤 USER FLOW
         * =========================
         */
        EmailVerification::where('email', $request->email)->delete();

        EmailVerification::create([
            'email'         => $request->email,
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'password'      => Hash::make($request->password),
            'otp'           => $otp,
            'expires_at'    => now()->addMinutes(10),

            'role'          => 'user',
            'invite_token'  => null,
        ]);

        Mail::send(
    'emails.otp-verification',
    [
        'otp' => $otp,
        'isAdmin' => false,
    ],
    function ($message) use ($request) {

        $message
            ->to($request->email)
            ->subject('KNOWURLOCAL | Email Verification');
    }
);

        return redirect('/otp?email=' . urlencode($request->email));
    }

    /**
     * =========================
     * 🔐 VERIFY OTP
     * =========================
     */
    public function verifyOtp(Request $request)
    {
        

        $request->validate([
            'otp' => 'required|digits:6',
            'email' => 'required|email'
        ]);

        $record = EmailVerification::where('email', $request->email)->first();
        

        if (!$record) {
            return back()->withErrors(['otp' => 'Invalid request']);
        }

        if (now()->greaterThan($record->expires_at)) {
            return back()->withErrors(['otp' => 'OTP expired']);
        }

        if ((string)$record->otp !== (string)trim($request->otp)) {
            return back()->withErrors(['otp' => 'Incorrect OTP']);
        }

        // 🔐 Create actual user
        $user = User::create([
            'first_name' => $record->first_name,
            'last_name'  => $record->last_name,
            'email'      => $record->email,
            'password'   => $record->password,
            'email_verified_at' => now(),
            'role' => $record->role ?? 'user',
            'status' => in_array($record->role, ['admin', 'superadmin'])
            ? 'pending'
            : 'active',
        ]);

        // 🔥 ADD THIS BLOCK (VERY IMPORTANT)
        // prevents auto-login / leftover session
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 🔐 Mark invite used
        if ($record->invite_token) {
            AdminInvite::where('token', $record->invite_token)
                ->update([
                    'used' => true,
                    'updated_at' => now()
                ]);
        }

        // 🧹 Cleanup
        $record->delete();

        if ($user->role === 'admin' || $user->role === 'superadmin') {
    return redirect('/admin/login')
        ->with(
            'success',
            'Your email has been verified successfully. Your admin account is now awaiting approval. You will be able to sign in once an administrator approves your account.'
        );
}

return redirect('/login-page')
    ->with(
        'success',
        'Your email has been verified. You can now log in to your KNOWURLOCAL account.'
    );
    }


    /**
 * =========================
 * 🔄 RESEND OTP
 * =========================
 */
public function resendOtp(Request $request)
{
    /*
     * Validate the email supplied by the OTP page.
     */
    $request->validate([
        'email' => 'required|email',
    ]);


    /*
     * Find the pending email verification record.
     *
     * This record tells us whether this is a public
     * user registration or an administrator registration.
     */
    $record = EmailVerification::where(
        'email',
        $request->email
    )->first();


    /*
     * If no pending verification exists, stop here.
     */
    if (!$record) {

        return response()->json([
            'success' => false,
            'message' =>
                'This verification request is no longer available. Please start the registration process again.',
        ], 422);
    }


    /*
     * Generate a new cryptographically secure OTP.
     */
    $otp = random_int(100000, 999999);


    /*
     * Replace the old OTP and reset its expiration.
     *
     * The previous OTP immediately becomes invalid.
     */
    $record->update([
        'otp' => $otp,
        'expires_at' => now()->addMinutes(10),
    ]);

    /*
     * Store the resend cooldown in the session.
     *
     * This is important because the OTP page can reload
     * after an incorrect verification attempt.
     */
    $request->session()->put(
        'otp_resend_available_at',
        now()->addSeconds(60)->timestamp
    );


    /*
     * Determine whether the pending registration belongs
     * to an administrator.
     *
     * We use the trusted database record instead of
     * accepting a role from the browser.
     */
    $isAdmin =
        in_array(
            $record->role,
            ['admin', 'superadmin'],
            true
        );


    /*
     * =========================
     * 👑 ADMIN OTP
     * =========================
     */
    if ($isAdmin) {

    Mail::send(
        'emails.otp-verification',
        [
            'otp' => $otp,
            'isAdmin' => true,
        ],
        function ($message) use ($request) {

            $message
                ->to($request->email)
                ->subject('KNOWURLOCAL | Email Verification');

        }
    );

} else {

    Mail::send(
        'emails.otp-verification',
        [
            'otp' => $otp,
            'isAdmin' => false,
        ],
        function ($message) use ($request) {

            $message
                ->to($request->email)
                ->subject('KNOWURLOCAL | Email Verification');

        }
    );

}


    /*
     * Return JSON because the OTP page uses fetch()
     * instead of performing a normal form submission.
     */
    return response()->json([
        'success' => true,
        'message' =>
            'A new verification code has been sent to your email address.',
    ]);
}


/**
 * =========================
 * 🔑 FORGOT PASSWORD PAGE
 * =========================
 *
 * Displays the password recovery form where the user
 * enters the email address associated with their account.
 */
public function showForgotPassword()
{
    return view('public_user.forgot-password');
}


/**
 * =========================
 * 🔐 SEND PASSWORD RESET OTP
 * =========================
 *
 * Generates and sends a secure OTP for password recovery.
 */
public function sendPasswordResetOtp(Request $request)
{
    /*
     * Validate the submitted email address.
     *
     * We intentionally do NOT use the "exists:users,email"
     * validation rule because that would reveal whether
     * an account exists in the system.
     */
    $request->validate([
        'email' => [
            'required',
            'email',
            'max:255',
        ],
    ]);


    /*
     * Normalize the email before using it.
     *
     * This prevents unnecessary differences such as
     * leading/trailing whitespace from creating separate
     * password-reset records.
     */
    $email = strtolower(trim($request->email));


    /*
     * Look for the account using the normalized email.
     */
    $user = User::where('email', $email)->first();


    /*
     * If the account does not exist, redirect back with
     * the same generic success message that we would use
     * for an existing account.
     *
     * This protects against account enumeration.
     */
    if (!$user) {

        return back()->with(
            'success',
            'If an account is associated with that email address, a verification code has been sent.'
        );
    }


    /*
     * Generate a cryptographically secure six-digit OTP.
     *
     * random_int() is appropriate for security-sensitive
     * values because it uses a cryptographically secure
     * random source.
     */
    $otp = (string) random_int(100000, 999999);


    /*
     * Remove any previous password-reset OTP for this
     * email address.
     *
     * This guarantees that only the newest OTP remains valid.
     */
    PasswordResetOtp::where('email', $email)->delete();


    /*
     * Store the OTP securely.
     *
     * We NEVER store the actual OTP in the database.
     * Hash::make() creates a one-way password-style hash.
     */
    PasswordResetOtp::create([
        'email' => $email,
        'otp' => Hash::make($otp),
        'expires_at' => now()->addMinutes(10),
        'attempts' => 0,
    ]);


    /*
     * Send the OTP using the application's configured
     * mail transport.
     *
     * Your existing Resend configuration is already
     * connected to Laravel's Mail system, so this uses
     * the same delivery infrastructure as registration.
     */
    Mail::send(
        'emails.password-reset-otp',
        [
            'otp' => $otp,
        ],
        function ($message) use ($email) {

            $message
                ->to($email)
                ->subject('KNOWURLOCAL | Password Reset Code');

        }
    );


    /*
     * Store the resend cooldown in the session.
     *
     * The OTP page can use this timestamp to prevent the
     * user from requesting another code for 60 seconds.
     */
    $request->session()->put(
        'password_reset_resend_available_at',
        now()->addSeconds(60)->timestamp
    );


    /*
     * Store the email in the session rather than trusting
     * a query parameter during the next recovery step.
     *
     * The reset flow will use this server-side value.
     */
    $request->session()->put(
        'password_reset_email',
        $email
    );


    /*
     * Redirect to the OTP verification page.
     */
    return redirect()
        ->route('password.otp')
        ->with(
            'success',
            'If an account is associated with that email address, a verification code has been sent.'
        );
}
    

/**
 * =========================
 * 🔢 PASSWORD RESET OTP PAGE
 * =========================
 *
 * Displays the OTP verification page for password recovery.
 */
public function showPasswordResetOtp(Request $request)
{
    /*
     * Retrieve the email stored in the server-side
     * password-reset session.
     */
    $email = $request->session()->get(
        'password_reset_email'
    );


    /*
     * If the user did not start a password-reset request,
     * there is no valid recovery session to continue.
     */
    if (!$email) {

        return redirect()
            ->route('password.request');

    }


    /*
     * Retrieve the server-side resend cooldown.
     */
    $availableAt = $request->session()->get(
        'password_reset_resend_available_at'
    );


    /*
     * Calculate the remaining cooldown in seconds.
     *
     * max(0, ...) prevents a negative value from being
     * sent to the frontend after the cooldown expires.
     */
    $resendRemaining = $availableAt
        ? max(
            0,
            $availableAt - now()->timestamp
        )
        : 0;


    /*
     * Display the password-reset OTP page.
     */
    return view(
        'public_user.password-reset-otp',
        compact(
            'email',
            'resendRemaining'
        )
    );
}

/**
 * =========================
 * 🔐 VERIFY PASSWORD RESET OTP
 * =========================
 *
 * Verifies the OTP before allowing the user
 * to create a new password.
 */
public function verifyPasswordResetOtp(Request $request)
{
    /*
     * Validate the OTP format.
     *
     * This is server-side validation and therefore cannot
     * be bypassed simply by modifying the browser.
     */
    $request->validate([
        'otp' => [
            'required',
            'digits:6',
        ],
    ]);


    /*
     * Retrieve the email from the protected recovery
     * session instead of accepting it from the browser.
     */
    $email = $request->session()->get(
        'password_reset_email'
    );


    /*
     * A missing recovery email means the user does not
     * have a valid password-reset flow in progress.
     */
    if (!$email) {

        return redirect()
            ->route('password.request')
            ->withErrors([
                'email' =>
                    'Your password reset session has expired. Please start again.',
            ]);

    }


    /*
     * Retrieve the OTP record associated with the
     * current password-reset session.
     */
    $record = PasswordResetOtp::where(
        'email',
        $email
    )->first();


    /*
     * Do not reveal whether an OTP record exists.
     *
     * From the user's perspective, the recovery request
     * simply becomes invalid and they can restart it.
     */
    if (!$record) {

        return redirect()
            ->route('password.request')
            ->withErrors([
                'email' =>
                    'Your password reset request is no longer available. Please start again.',
            ]);

    }


    /*
     * Check whether the OTP has expired.
     */
    if (
        now()->greaterThan(
            $record->expires_at
        )
    ) {

        /*
         * Delete the expired credential so it cannot
         * accidentally remain usable later.
         */
        $record->delete();


        return back()->withErrors([
            'otp' =>
                'This verification code has expired. Please request a new code.',
        ]);

    }


    /*
     * Check whether the maximum number of failed
     * verification attempts has already been reached.
     */
    if ($record->attempts >= 5) {

        /*
         * Delete the OTP after the attempt limit is reached.
         *
         * This makes the current code permanently unusable.
         */
        $record->delete();


        return redirect()
            ->route('password.request')
            ->withErrors([
                'email' =>
                    'Too many incorrect verification attempts. Please request a new code.',
            ]);

    }


    /*
     * Compare the submitted OTP against the HASHED OTP
     * stored in the database.
     *
     * Hash::check() safely verifies a hash without exposing
     * the original OTP.
     */
    if (!Hash::check(
        trim($request->otp),
        $record->otp
    )) {

        /*
         * Increase the failed-attempt counter.
         */
        $record->increment('attempts');


        /*
         * Calculate how many attempts remain.
         */
        $remainingAttempts =
            max(
                0,
                5 - ($record->attempts)
            );


        /*
         * If the fifth attempt failed, immediately
         * invalidate the OTP.
         */
        if ($record->attempts >= 5) {

            $record->delete();


            return redirect()
                ->route('password.request')
                ->withErrors([
                    'email' =>
                        'Too many incorrect verification attempts. Please request a new code.',
                ]);

        }


        return back()->withErrors([
            'otp' =>
                "Incorrect verification code. {$remainingAttempts} attempts remaining.",
        ]);

    }


    /*
     * =====================================================
     * OTP SUCCESS
     * =====================================================
     *
     * The user has successfully demonstrated control of
     * the email account that received the recovery code.
     */


    /*
     * Delete the OTP immediately.
     *
     * This prevents the same OTP from being reused.
     */
    $record->delete();


    /*
     * Create a server-side authorization marker.
     *
     * The next password-reset page will require this value.
     */
    $request->session()->put(
        'password_reset_verified',
        true
    );


    /*
     * Redirect to the new-password page.
     */
    return redirect()
        ->route('password.reset')
        ->with(
            'success',
            'Your verification code has been confirmed. You can now create a new password.'
        );
}


/**
 * =========================
 * 🔄 RESEND PASSWORD RESET OTP
 * =========================
 *
 * Generates a replacement OTP for the current
 * password-reset session.
 */
public function resendPasswordResetOtp(Request $request)
{
    /*
     * Retrieve the email from the server-side recovery
     * session.
     */
    $email = $request->session()->get(
        'password_reset_email'
    );


    /*
     * Stop if the recovery flow does not exist.
     */
    if (!$email) {

        return response()->json([
            'success' => false,
            'message' =>
                'Your password reset session has expired. Please start again.',
        ], 422);

    }


    /*
     * Check the server-side resend cooldown.
     */
    $availableAt = $request->session()->get(
        'password_reset_resend_available_at'
    );


    /*
     * If the cooldown is still active, reject the request.
     */
    if (
        $availableAt &&
        now()->timestamp < $availableAt
    ) {

        $remaining =
            $availableAt - now()->timestamp;


        return response()->json([
            'success' => false,
            'message' =>
                "Please wait {$remaining} seconds before requesting another code.",
        ], 429);

    }


    /*
     * Generate a new cryptographically secure OTP.
     */
    $otp = (string) random_int(
        100000,
        999999
    );


    /*
     * Remove the previous OTP.
     *
     * This guarantees that only the newest code can
     * be successfully verified.
     */
    PasswordResetOtp::where(
        'email',
        $email
    )->delete();


    /*
     * Store the new OTP as a hash.
     */
    PasswordResetOtp::create([
        'email' => $email,

        'otp' => Hash::make($otp),

        'expires_at' =>
            now()->addMinutes(10),

        'attempts' => 0,
    ]);


    /*
     * Start a new 60-second resend cooldown.
     */
    $request->session()->put(
        'password_reset_resend_available_at',
        now()->addSeconds(60)->timestamp
    );


    /*
     * Send the replacement verification code.
     */
    Mail::send(
        'emails.password-reset-otp',
        [
            'otp' => $otp,
        ],
        function ($message) use ($email) {

            $message
                ->to($email)
                ->subject(
                    'KNOWURLOCAL | Password Reset Code'
                );

        }
    );


    /*
     * Return JSON because the OTP page will request
     * the resend operation using JavaScript fetch().
     */
    return response()->json([
        'success' => true,
        'message' =>
            'A new verification code has been sent to your email address.',
    ]);
}

public function showResetPassword(Request $request)
{
    /*
     * The user can only reach the new-password page
     * after successfully verifying the password-reset OTP.
     *
     * This value is stored server-side in the session.
     * We do NOT trust a URL parameter such as ?verified=true.
     */
    if (!$request->session()->get('password_reset_verified')) {
        return redirect()
            ->route('password.request')
            ->withErrors([
                'email' => 'Please verify your account before resetting your password.',
            ]);
    }


    /*
     * Retrieve the email that was stored when the
     * password-reset process started.
     */
    $email = $request->session()->get('password_reset_email');


    /*
     * If the email is missing, the reset session is incomplete.
     */
    if (!$email) {
        return redirect()
            ->route('password.request')
            ->withErrors([
                'email' => 'Your password reset session has expired. Please start again.',
            ]);
    }


    /*
     * Pass only the necessary information to the view.
     */
    return view(
        'public_user.reset-password',
        compact('email')
    );
}


public function resetPassword(Request $request)
{
    /*
     * Never allow this endpoint to change a password unless
     * the OTP verification step was successfully completed.
     */
    if (!$request->session()->get('password_reset_verified')) {
        return redirect()
            ->route('password.request')
            ->withErrors([
                'email' => 'Your password reset session has expired. Please start again.',
            ]);
    }


    /*
     * Retrieve the verified email from the server-side session.
     */
    $email = $request->session()->get('password_reset_email');


    /*
     * Without the verified email, we cannot safely determine
     * which account should receive the new password.
     */
    if (!$email) {
        return redirect()
            ->route('password.request')
            ->withErrors([
                'email' => 'Your password reset session has expired. Please start again.',
            ]);
    }


    /*
     * Validate the new password on the server.
     *
     * "confirmed" requires a matching password_confirmation field.
     */
    $validated = $request->validate([
        'password' => [
            'required',
            'string',
            'min:8',
            'confirmed',
        ],
    ]);


    /*
     * Find the account using the email stored in the
     * server-side reset session.
     *
     * We deliberately do NOT accept the email from the browser
     * as the account identifier.
     */
    $user = User::where('email', $email)->first();


    /*
     * The account may have been removed after the OTP was issued.
     */
    if (!$user) {
        $request->session()->forget([
            'password_reset_email',
            'password_reset_verified',
            'password_reset_resend_available_at',
        ]);

        return redirect()
            ->route('password.request')
            ->withErrors([
                'email' => 'Unable to complete the password reset. Please start again.',
            ]);
    }


    /*
     * Hash the new password before writing it to the database.
     *
     * The plain-text password is never stored.
     */
    $user->password = Hash::make($validated['password']);

    $user->save();


    /*
     * The password-reset authorization must be destroyed
     * immediately after a successful password change.
     *
     * This prevents the same verified reset session from
     * being reused.
     */
    $request->session()->forget([
        'password_reset_email',
        'password_reset_verified',
        'password_reset_resend_available_at',
    ]);


    /*
     * Regenerate the session ID after the security-sensitive
     * operation to reduce session-fixation risk.
     */
    $request->session()->regenerate();


    /*
     * Send the user back to the normal login page.
     */
    return redirect()
        ->route('public.login')
        ->with(
            'success',
            'Your password has been reset successfully. You can now log in with your new password.'
        );
}


    /**
     * =========================
     * 🔐 LOGIN
     * =========================
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->email_verified_at) {
            return back()->withErrors([
                'email' => 'Please verify your email first'
            ]);
        }

        if ($user->status !== 'active') {
    return back()->withErrors([
        'email' =>
            'Your admin account is awaiting approval. You can sign in once an administrator has approved your account.'
    ]);
}

        if (Auth::attempt($request->only('email','password'))) {

            $request->session()->regenerate();

            /*
            * Record the most recent successful login.
            *
            * This is intentionally updated only after
            * authentication succeeds.
            */
            $user = Auth::user();

            $user->update([
                'last_login_at' => now(),
            ]);

            UserLog::create([
                'user_id' => Auth::id(),
                'action' => 'login',
                'page' => 'login',
                'ip_address' => $request->ip(),
                'device' => $request->userAgent(),
            ]);

            if (in_array(Auth::user()->role, ['admin','superadmin'])) {
                return redirect('/admin/dashboard');
            }

            return redirect('/map');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials'
        ]);
    }

    /**
     * =========================
     * 🔐 LOGOUT
     * =========================
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            UserLog::create([
                'user_id' => $user->id,
                'action' => 'logout',
                'page' => 'navbar',
                'ip_address' => $request->ip(),
                'device' => substr($request->userAgent(), 0, 255),
                'role' => $user->role,
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user && in_array($user->role, ['admin','superadmin'])) {
            return redirect('/admin/login');
        }

        return redirect('/login-page');
    }
}