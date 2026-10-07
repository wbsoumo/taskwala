<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
}
