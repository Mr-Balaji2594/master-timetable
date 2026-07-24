<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function create()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Login', [
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'emp_id' => 'required|string',
            'department_id' => 'required|integer|exists:departments,id',
            'password' => 'required|string',
        ]);

        if (!check_login_attempts($credentials['emp_id'])) {
            return back()->withErrors([
                'emp_id' => 'Too many failed attempts. Please try again after ' . LOGIN_LOCKOUT_MINUTES . ' minutes.',
            ]);
        }

        if (Auth::attempt([
            'emp_id' => $credentials['emp_id'],
            'password' => $credentials['password'],
            'department_id' => $credentials['department_id'],
        ], $request->boolean('remember'))) {
            $request->session()->regenerate();
            record_login_attempt($credentials['emp_id'], true);
            audit_log('login', "User {$credentials['emp_id']} logged in");

            return redirect()->intended(route('dashboard'));
        }

        record_login_attempt($credentials['emp_id'], false);

        return back()->withErrors([
            'emp_id' => 'Invalid Employee ID, Department, or Password.',
        ]);
    }

    public function destroy(Request $request)
    {
        audit_log('logout', "User " . (Auth::user()->emp_id ?? 'unknown') . " logged out");
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
