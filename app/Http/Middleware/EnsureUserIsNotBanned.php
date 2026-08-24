<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBanned
{
    /**
     * Block access for banned users and immediately terminate their session.
     * This enforces the `is_banned` flag at the session level, not just at login time,
     * so an account banned mid-session loses access on its very next request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If an admin is impersonating a banned user, allow the admin to inspect the account without terminating the session
        if (Auth::check() && $request->session()->has('impersonated_by_admin_id')) {
            return $next($request);
        }

        if (Auth::check() && Auth::user()->is_banned) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been banned. Please contact support for assistance.',
            ]);
        }

        return $next($request);
    }
}
