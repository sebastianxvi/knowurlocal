<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicUserProfileController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'user', 403);

        $evaluations = $request->user()->agencyEvaluations()
            ->with(['agency:id,agency_name,agency_image,agency_abbreviation'])
            ->latest('updated_at')
            ->get(['id', 'agency_id', 'rating', 'comment', 'status', 'created_at', 'updated_at']);

        return view('public_user.profile', [
            'user' => $request->user(),
            'evaluations' => $evaluations,
        ]);
    }

    public function updateDetails(Request $request)
    {
        abort_unless($request->user()->role === 'user', 403);
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'current_password'],
        ]);

        if (isset($validated['first_name'])) $user->first_name = trim($validated['first_name']);
        if (isset($validated['last_name'])) $user->last_name = trim($validated['last_name']);
        if (isset($validated['email'])) {
            $newEmail = strtolower(trim($validated['email']));
            if ($newEmail !== $user->email) {
                $user->email = $newEmail;
                $user->email_verified_at = null;
            }
        }

        $user->save();

        return response()->json(['message' => 'Your profile details have been updated.'])
            ->header('Cache-Control', 'private, no-store');
    }

    public function updatePhoto(Request $request)
    {
        abort_unless($request->user()->role === 'user', 403);
        $validated = $request->validate([
            'profile_picture' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();
        $path = $validated['profile_picture']->store('public-user-profiles', 'public');
        if (!$path) {
            throw ValidationException::withMessages(['profile_picture' => 'The profile picture could not be saved.']);
        }

        $previousPath = $user->profile_picture;
        $user->profile_picture = $path;
        $user->save();

        // Delete the previous file only after the new path has been saved.
        if ($previousPath && $previousPath !== $path && Storage::disk('public')->exists($previousPath)) {
            Storage::disk('public')->delete($previousPath);
        }

        return response()->json([
            'message' => 'Your profile picture has been updated.',
            'url' => Storage::disk('public')->url($path),
        ])->header('Cache-Control', 'private, no-store');
    }

    /**
     * Issue the existing password-reset OTP for an authenticated public user.
     * It uses the shared PasswordResetOtp model, hashed code storage, and
     * password-reset email template rather than a separate OTP implementation.
     */
    public function requestPasswordOtp(Request $request)
    {
        abort_unless($request->user()->role === 'user', 403);
        $user = $request->user();
        $recent = PasswordResetOtp::where('email', $user->email)
            ->where('created_at', '>=', now()->subMinute())
            ->exists();

        if ($recent) {
            return response()->json([
                'message' => 'Please wait one minute before requesting another code.',
            ], 429)->header('Cache-Control', 'private, no-store');
        }

        $otp = (string) random_int(100000, 999999);

        PasswordResetOtp::where('email', $user->email)->delete();
        $record = PasswordResetOtp::create([
            'email' => $user->email,
            'otp' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        try {
            Mail::send('emails.password-reset-otp', ['otp' => $otp], function ($message) use ($user) {
                $message->to($user->email)->subject('KNOWURLOCAL | Password Reset Code');
            });
        } catch (\Throwable $exception) {
            $record->delete();
            report($exception);

            return response()->json([
                'message' => 'The verification email could not be sent. Please try again later.',
            ], 503)->header('Cache-Control', 'private, no-store');
        }

        return response()->json([
            'message' => 'A verification code has been sent to your account email.',
        ])->header('Cache-Control', 'private, no-store');
    }

    /**
     * Verify the emailed code before allowing the user to open the
     * password form. This is separate from registration's browser page,
     * but uses the same PasswordResetOtp records and six-digit code rules.
     */
    public function verifyPasswordOtp(Request $request)
    {
        abort_unless($request->user()->role === 'user', 403);
        $validated = $request->validate(['otp' => ['required', 'digits:6']]);
        $user = $request->user();

        $outcome = DB::transaction(function () use ($user, $validated) {
            $record = PasswordResetOtp::where('email', $user->email)
                ->lockForUpdate()
                ->first();

            if (!$record || now()->greaterThan($record->expires_at)) {
                if ($record) $record->delete();
                return ['error' => 'The verification code expired. Please request a new one.'];
            }

            if ($record->attempts >= 5) {
                $record->delete();
                return ['error' => 'Too many incorrect verification attempts. Please request a new code.'];
            }

            if (!Hash::check(trim($validated['otp']), $record->otp)) {
                $record->increment('attempts');

                if ($record->attempts >= 5) {
                    $record->delete();
                    return ['error' => 'Too many incorrect verification attempts. Please request a new code.'];
                }

                return ['error' => 'Incorrect verification code. ' . max(0, 5 - $record->attempts) . ' attempts remaining.'];
            }

            return ['success' => true];
        });

        if (isset($outcome['error'])) {
            throw ValidationException::withMessages(['otp' => $outcome['error']]);
        }

        $request->session()->put('profile_password_otp_verified', [
            'user_id' => $user->id,
            'email' => $user->email,
            'verified_at' => now()->timestamp,
        ]);

        return response()->json(['message' => 'Email verification successful. You can now choose a new password.'])
            ->header('Cache-Control', 'private, no-store');
    }

    public function changePasswordWithOtp(Request $request)
    {
        abort_unless($request->user()->role === 'user', 403);

        $user = $request->user();
        $verification = $request->session()->get('profile_password_otp_verified');
        $isVerified = is_array($verification)
            && (int) ($verification['user_id'] ?? 0) === (int) $user->id
            && ($verification['email'] ?? null) === $user->email
            && isset($verification['verified_at'])
            && now()->timestamp - (int) $verification['verified_at'] <= 600;

        if (!$isVerified) {
            throw ValidationException::withMessages([
                'otp' => 'Please verify the email code again before changing your password.',
            ]);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $record = PasswordResetOtp::where('email', $user->email)
                ->lockForUpdate()
                ->first();

            // The OTP must remain valid until the password update commits.
            if (!$record || now()->greaterThan($record->expires_at) || $record->attempts >= 5) {
                throw ValidationException::withMessages([
                    'otp' => 'The verification code expired. Please request a new one.',
                ]);
            }

            $user->password = $validated['password'];
            $user->save();
            $record->delete();
        });

        $request->session()->forget('profile_password_otp_verified');

        return response()->json(['message' => 'Your password has been changed.'])
            ->header('Cache-Control', 'private, no-store');
    }
}
