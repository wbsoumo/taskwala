<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('user.dashboard');
        }

        return view('user.auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'upi_id' => ['nullable', 'string', 'max:255'],
            'upi_holder_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'mobile_number' => $validated['mobile_number'],
            'password' => Hash::make($validated['password']),
            'status' => 'active',
            'upi_id' => $validated['upi_id'] ?? null,
            'upi_holder_name' => $validated['upi_holder_name'] ?? null,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        Auth::guard('web')->login($user);

        AuditService::log(
            action: 'user_registered',
            entityType: User::class,
            entityId: $user->id,
            newValues: ['name' => $user->name, 'email' => $user->email],
            actorType: 'user',
            actorId: $user->id
        );

        return redirect()->route('user.dashboard')->with('success', 'Welcome to the platform!');
    }
}
