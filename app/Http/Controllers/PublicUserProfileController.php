<?php

namespace App\Http\Controllers;

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

        if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $user->profile_picture = $path;
        $user->save();

        return response()->json([
            'message' => 'Your profile picture has been updated.',
            'url' => Storage::disk('public')->url($path),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function requestPasswordOtp(Request $request)
    {
        abort_unless($request->user()->role === 'user', 403);
        $request->validate(['current_password' => ['required', 'current_password']]);

        $user = $request->user();
        $recent = DB::table('public_user_password_otps')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subMinute())
            ->exists();
        if ($recent) {
            return response()->json(['message' => 'Please wait one minute before requesting another code.'], 429);
        }

        $otp = (string) random_int(100000, 999999);
        DB::table('public_user_password_otps')->where('user_id', $user->id)->delete();
        DB::table('public_user_password_otps')->insert([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'created_at' => now(),
        ]);

        try {
            Mail::raw(
                "Your KNOWURLOCAL password change verification code is: {$otp}\n\nThis code expires in 10 minutes. If you did not request this, you can ignore this email.",
                function ($message) use ($user) {
                    $message->to($user->email)->subject('KNOWURLOCAL password change verification');
                }
            );
        } catch (\Throwable $exception) {
            DB::table('public_user_password_otps')->where('user_id', $user->id)->delete();
            report($exception);
            return response()->json(['message' => 'The verification email could not be sent. Please try again later.'], 503);
        }

        return response()->json(['message' => 'A verification code has been sent to your account email.'])
            ->header('Cache-Control', 'private, no-store');
    }

    public function changePasswordWithOtp(Request $request)
    {
        abort_unless($request->user()->role === 'user', 403);
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $record = DB::table('public_user_password_otps')->where('user_id', $user->id)->first();

        if (!$record || now()->greaterThan($record->expires_at) || $record->attempts >= 5) {
            DB::table('public_user_password_otps')->where('user_id', $user->id)->delete();
            throw ValidationException::withMessages(['otp' => 'The verification code expired. Please request a new one.']);
        }

        if (!Hash::check($validated['otp'], $record->otp_hash)) {
            DB::table('public_user_password_otps')->where('id', $record->id)->increment('attempts');
            throw ValidationException::withMessages(['otp' => 'The verification code is incorrect.']);
        }

        $user->password = $validated['password'];
        $user->save();
        DB::table('public_user_password_otps')->where('user_id', $user->id)->delete();

        return response()->json(['message' => 'Your password has been changed.'])
            ->header('Cache-Control', 'private, no-store');
    }
}
