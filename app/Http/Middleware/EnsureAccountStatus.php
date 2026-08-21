<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        $path = $request->path();

        // Deactivated accounts are signed out immediately on any request.
        if ($user->is_active === false) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Your account has been deactivated. Please contact the administrator.');
        }

        // Accounts flagged for a mandatory password change may only visit the
        // change-password screen (and log out) until the password is updated.
        if ($user->must_change_password && !in_array($path, ['change-password', 'logout'], true)) {
            return redirect()->route('change-password.index')->with('error', 'You must change your password before continuing.');
        }

        return $next($request);
    }
}