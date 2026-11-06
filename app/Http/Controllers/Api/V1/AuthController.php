<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpMailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'login' => ['required', 'string'], // Email, Mobile number, or Name
                'password' => ['required', 'string'],
            ]);

            $login = trim($request->login);

            $user = User::where('email', $login)
                ->orWhere('mobile_number', $login)
                ->orWhere('name', $login)
                ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'login' => ['Invalid email/mobile or password credentials.'],
                ]);
            }

            if (!$user->isActive()) {
                return response()->json(['message' => 'Account is suspended or blocked.'], 403);
            }

            // Email Verification check: Always verify user's email id
            if ($user->email_verified_at === null) {
                try {
                    $otp = (string) random_int(100000, 999999);
                    DB::table('password_reset_tokens')->updateOrInsert(
                        ['email' => $user->email],
                        [
                            'token' => Hash::make($otp),
                            'created_at' => now(),
                        ]
                    );

                    OtpMailService::sendRegistrationVerificationOtp($user->email, $user->name, $otp);
                } catch (\Throwable $mailEx) {
                    Log::warning('Verification email dispatch failed: ' . $mailEx->getMessage());
                }

                $responseData = [
                    'success' => false,
                    'requires_verification' => true,
                    'message' => 'Please verify your email address to log in. We have sent a verification code to ' . $this->maskEmail($user->email),
                    'email' => $user->email,
                    'email_hint' => $this->maskEmail($user->email),
                ];

                if (app()->environment('testing')) {
                    $responseData['debug_code'] = $otp;
                }

                return response()->json($responseData, 403);
            }

            $token = $user->createToken('mobile_app_token')->plainTextToken;

            if ($request->filled('fcm_token')) {
                try {
                    if (Schema::hasColumn('users', 'fcm_token')) {
                        $user->update([
                            'fcm_token' => $request->fcm_token,
                            'fcm_token_updated_at' => now(),
                        ]);
                    }
                } catch (\Throwable $fcmEx) {
                    Log::warning('FCM token saving bypassed: ' . $fcmEx->getMessage());
                }
            }

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
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('API login error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Login error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function register(Request $request): JsonResponse
    {
        try {
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
                'email_verified_at' => null,
            ]);

            try {
                $otp = (string) random_int(100000, 999999);
                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email' => $user->email],
                    [
                        'token' => Hash::make($otp),
                        'created_at' => now(),
                    ]
                );

                OtpMailService::sendRegistrationVerificationOtp($user->email, $user->name, $otp);
            } catch (\Throwable $otpEx) {
                Log::warning('OTP dispatch failed: ' . $otpEx->getMessage());
            }

            $responseData = [
                'success' => true,
                'requires_verification' => true,
                'message' => 'Registration successful! Please verify your email with the 6-digit code sent to ' . $this->maskEmail($user->email),
                'email' => $user->email,
                'email_hint' => $this->maskEmail($user->email),
            ];

            if (app()->environment('testing')) {
                $responseData['debug_code'] = $otp ?? null;
            }

            return response()->json($responseData, 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('API registration error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Registration error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $email = trim($request->email);
        $user = User::where('email', $email)
            ->orWhere('mobile_number', $email)
            ->orWhere('name', $email)
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
                'message' => 'Verification code has expired. Please request a new code.',
            ], 422);
        }

        $user->email_verified_at = now();
        $user->save();

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        $token = $user->createToken('mobile_app_token')->plainTextToken;

        if ($request->filled('fcm_token')) {
            try {
                if (Schema::hasColumn('users', 'fcm_token')) {
                    $user->update([
                        'fcm_token' => $request->fcm_token,
                        'fcm_token_updated_at' => now(),
                    ]);
                }
            } catch (\Throwable $fcmEx) {
                Log::warning('FCM token saving bypassed in verifyEmail: ' . $fcmEx->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully! Welcome to Taskwala.',
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

    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string'],
        ]);

        $email = trim($request->email);
        $user = User::where('email', $email)
            ->orWhere('mobile_number', $email)
            ->orWhere('name', $email)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        if ($user->email_verified_at !== null) {
            return response()->json([
                'success' => true,
                'message' => 'Email is already verified. You can log in.',
                'already_verified' => true,
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

        OtpMailService::sendRegistrationVerificationOtp($user->email, $user->name, $otp);

        $responseData = [
            'success' => true,
            'message' => 'A fresh verification code has been sent to your email (' . $this->maskEmail($user->email) . ').',
            'email' => $user->email,
            'email_hint' => $this->maskEmail($user->email),
        ];

        if (app()->environment('testing')) {
            $responseData['debug_code'] = $otp;
        }

        return response()->json($responseData);
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

        // Search user by email, mobile number, or name
        $user = User::where('email', $login)
            ->orWhere('mobile_number', $login)
            ->orWhere('name', $login)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found matching this name, email or mobile number.',
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

        // Always send OTP code strictly to the user's registered email address
        OtpMailService::sendPasswordResetOtp($user->email, $user->name, $otp);

        $responseData = [
            'success' => true,
            'message' => 'A 6-digit verification code has been sent to your registered email (' . $this->maskEmail($user->email) . ').',
            'login' => $login,
            'email_hint' => $this->maskEmail($user->email),
            'mobile_hint' => $this->maskMobile($user->mobile_number),
        ];

        if (app()->environment('testing')) {
            $responseData['debug_code'] = $otp;
        }

        return response()->json($responseData);
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
            ->orWhere('name', $login)
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
            ->orWhere('name', $login)
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

        // Update password and ensure email is marked verified
        $user->password = Hash::make($request->password);
        if ($user->email_verified_at === null) {
            $user->email_verified_at = now();
        }
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

    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => ['required', 'string'],
        ]);

        try {
            if (Schema::hasColumn('users', 'fcm_token')) {
                $user = $request->user();
                $user->update([
                    'fcm_token' => $request->fcm_token,
                    'fcm_token_updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('FCM token update failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'FCM device token registered successfully.',
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
