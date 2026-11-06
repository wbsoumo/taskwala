<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('user.auth.forgot-password');
    }

    public function sendResetOtp(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
        ]);

        $login = trim($request->login);

        $user = User::where('email', $login)
            ->orWhere('mobile_number', $login)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'login' => 'No account found matching this email or mobile number.',
            ]);
        }

        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                'login' => 'Account is suspended or blocked. Please contact support.',
            ]);
        }

        $otp = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($otp),
                'created_at' => now(),
            ]
        );

        try {
            Mail::raw("Your Taskwala verification code is: {$otp}\n\nThis code will expire in 15 minutes. If you did not request a password reset, please secure your account.", function ($message) use ($user) {
                $message->to($user->email)->subject('Taskwala - Password Reset Verification Code');
            });
        } catch (\Throwable $e) {
            Log::warning('Password reset email error: ' . $e->getMessage());
        }

        session(['reset_login' => $login]);

        return redirect()->route('user.password.reset')->with('status', 'Verification code sent successfully. Please check your contact/email.');
    }

    public function showResetForm(Request $request)
    {
        $login = session('reset_login', $request->query('login', ''));
        return view('user.auth.reset-password', [
            'login' => $login,
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $login = trim($request->login);
        $user = User::where('email', $login)
            ->orWhere('mobile_number', $login)
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'login' => 'User not found.',
            ]);
        }

        $record = DB::table('password_reset_tokens')->where('email', $user->email)->first();

        if (!$record || !Hash::check($request->otp, $record->token)) {
            throw ValidationException::withMessages([
                'otp' => 'Invalid or incorrect verification code.',
            ]);
        }

        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            throw ValidationException::withMessages([
                'otp' => 'Verification code has expired. Please request a new one.',
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        $user->tokens()->delete();

        session()->forget('reset_login');

        return redirect()->route('user.login')->with('status', 'Your password has been reset successfully. You can now log in.');
    }
}
