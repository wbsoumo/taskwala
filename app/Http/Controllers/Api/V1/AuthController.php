<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => ['required', 'string'], // Email or Mobile number
            'password' => ['required', 'string'],
        ]);

        $login = trim($request->login);

        $user = User::where('email', $login)
            ->orWhere('mobile_number', $login)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid email/mobile or password credentials.'],
            ]);
        }

        if (!$user->isActive()) {
            return response()->json(['message' => 'Account is suspended or blocked.'], 403);
        }

        $token = $user->createToken('mobile_app_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => [
                'public_id' => $user->public_id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile_number' => $user->mobile_number,
                'referral_code' => $user->referral_code,
                'upi_id' => $user->upi_id,
                'upi_holder_name' => $user->upi_holder_name,
                'status' => $user->status,
            ],
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'mobile_number' => ['required', 'string', 'max:20', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'referral_code' => ['nullable', 'string', 'exists:users,referral_code'],
        ]);

        $referrerId = null;
        if (!empty($validated['referral_code'])) {
            $referrer = User::where('referral_code', $validated['referral_code'])->first();
            $referrerId = $referrer?->id;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'mobile_number' => $validated['mobile_number'],
            'password' => Hash::make($validated['password']),
            'referred_by' => $referrerId,
            'status' => 'active',
        ]);

        $token = $user->createToken('mobile_app_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'token' => $token,
            'user' => [
                'public_id' => $user->public_id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile_number' => $user->mobile_number,
                'referral_code' => $user->referral_code,
                'status' => $user->status,
            ],
        ], 201);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load('wallet');

        return response()->json([
            'user' => [
                'public_id' => $user->public_id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile_number' => $user->mobile_number,
                'upi_id' => $user->upi_id,
                'upi_holder_name' => $user->upi_holder_name,
                'referral_code' => $user->referral_code,
                'status' => $user->status,
                'wallet' => $user->wallet,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
        ]);

        $login = trim($request->login);

        $user = User::where('email', $login)
            ->orWhere('mobile_number', $login)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found matching this email or mobile number.',
            ], 404);
        }

        if (!$user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Account is suspended or blocked. Please contact support.',
            ], 403);
        }

        // Generate 6-digit verification code
        $otp = (string) random_int(100000, 999999);

        // Store hashed OTP in password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($otp),
                'created_at' => now(),
            ]
        );

        // Send OTP email if mailer is configured
        try {
            Mail::raw("Your Taskwala verification code is: {$otp}\n\nThis code will expire in 15 minutes. If you did not request a password reset, please secure your account immediately.", function ($message) use ($user) {
                $message->to($user->email)->subject('Taskwala - Password Reset Verification Code');
            });
        } catch (\Throwable $e) {
            Log::warning('Password reset email error: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'A 6-digit verification code has been sent.',
            'login' => $login,
            'email_hint' => $this->maskEmail($user->email),
            'mobile_hint' => $this->maskMobile($user->mobile_number),
            'debug_code' => $otp, // For smooth dev/testing flow
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $login = trim($request->login);
        $user = User::where('email', $login)
            ->orWhere('mobile_number', $login)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $record = DB::table('password_reset_tokens')->where('email', $user->email)->first();

        if (!$record || !Hash::check($request->otp, $record->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or incorrect verification code.',
            ], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code has expired. Please request a new one.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code verified successfully.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
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
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $record = DB::table('password_reset_tokens')->where('email', $user->email)->first();

        if (!$record || !Hash::check($request->otp, $record->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or incorrect verification code.',
            ], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code has expired. Please request a new one.',
            ], 422);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        // Clear reset token
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        // Revoke old tokens
        $user->tokens()->delete();

        // Generate fresh token
        $token = $user->createToken('mobile_app_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. You are now logged in.',
            'token' => $token,
            'user' => [
                'public_id' => $user->public_id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile_number' => $user->mobile_number,
                'referral_code' => $user->referral_code,
                'upi_id' => $user->upi_id,
                'upi_holder_name' => $user->upi_holder_name,
                'status' => $user->status,
            ],
        ]);
    }

    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';
        $len = strlen($name);
        if ($len <= 2) {
            $maskedName = $name . '***';
        } else {
            $maskedName = substr($name, 0, 1) . str_repeat('*', max(3, $len - 2)) . substr($name, -1);
        }
        return $maskedName . '@' . $domain;
    }

    protected function maskMobile(?string $mobile): string
    {
        if (empty($mobile)) return '';
        $clean = preg_replace('/\D/', '', $mobile);
        $len = strlen($clean);
        if ($len < 4) return $mobile;
        return substr($clean, 0, 4) . '****' . substr($clean, -2);
    }
}
