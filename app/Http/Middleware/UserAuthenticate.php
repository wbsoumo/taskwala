<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('web')->check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated user access.'], 401);
            }
            return redirect()->route('user.login');
        }

        $user = Auth::guard('web')->user();
        if (!$user->isActive()) {
            Auth::guard('web')->logout();
            return redirect()->route('user.login')->withErrors(['email' => 'Your affiliate account is inactive or suspended.']);
        }

        return $next($request);
    }
}
