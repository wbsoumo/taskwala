<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('admin')->check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated Admin access.'], 401);
            }
            return redirect()->route('admin.login');
        }

        $admin = Auth::guard('admin')->user();
        if (!$admin->isActive()) {
            Auth::guard('admin')->logout();
            return redirect()->route('admin.login')->withErrors(['email' => 'Your admin account is suspended.']);
        }

        return $next($request);
    }
}
