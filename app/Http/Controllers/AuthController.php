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
            Mail::raw("Your KNOWURLOCAL Admin OTP is: $otp", function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Admin Verification Code');
            });

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

        Mail::raw("Your KNOWURLOCAL OTP is: $otp", function ($message) use ($request) {
            $message->to($request->email)
                    ->subject('Verification Code');
        });

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

        // 🔐 Redirect properly
        if ($user->role === 'admin' || $user->role === 'superadmin') {
            return redirect('/admin/login')
                ->with('success', 'Admin account created. Please login.');
        }

        return redirect('/login-page')
            ->with('success', 'Account created successfully.');
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
                'email' => 'Account not yet approved.'
            ]);
        }

        if (Auth::attempt($request->only('email','password'))) {

            $request->session()->regenerate();

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