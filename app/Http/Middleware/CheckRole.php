<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        foreach ($roles as $role) {
            if ($role === 'management' && $user->isManagement()) {
                return $next($request);
            }
            if ($role === 'admin' && $user->isAdmin()) {
                return $next($request);
            }
            if ($role === 'super_admin' && $user->isSuperAdmin()) {
                return $next($request);
            }
            if ($role === 'principal' && $user->isPrincipal()) {
                return $next($request);
            }
            if ($role === 'vice_principal' && $user->isVicePrincipal()) {
                return $next($request);
            }
            if ($role === 'hod' && $user->isHOD()) {
                return $next($request);
            }
            if ($role === 'staff' && $user->isStaff()) {
                return $next($request);
            }
            if ($role === 'any') {
                return $next($request);
            }
        }

        abort(403, 'Access denied.');
    }
}
