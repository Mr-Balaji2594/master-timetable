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
            'emp_id'        => 'required|string',
            'department_id' => 'required|integer|exists:departments,id',
            'password'      => 'required|string',
        ]);

        // Check is_active FIRST — before rate-limit — so deactivated accounts
        // never count as failed attempts and never get locked out.
        $employee = \App\Models\Employee::where('emp_id', $credentials['emp_id'])
            ->where('department_id', $credentials['department_id'])
            ->first();

        if ($employee && $employee->is_active === false) {
            audit_log('login_blocked', "Blocked login for deactivated employee {$credentials['emp_id']}");
            return back()->withErrors([
                'emp_id' => 'Your account has been deactivated. Please contact the administrator.',
            ]);
        }

        // Rate-limit check only applies to active (or unknown) accounts
        if (!check_login_attempts($credentials['emp_id'])) {
            $minutes = (int) ceil(login_lockout_remaining_seconds($credentials['emp_id']) / 60);
            audit_log('login_locked', "Login lockout triggered for employee {$credentials['emp_id']}");
            return back()->withErrors([
                'emp_id' => 'Too many failed attempts. Please try again after ' . $minutes . ' minute(s).',
            ]);
        }

        if (Auth::attempt([
            'emp_id'        => $credentials['emp_id'],
            'password'      => $credentials['password'],
            'department_id' => $credentials['department_id'],
        ], $request->boolean('remember'))) {

            // Double-check is_active in case it changed mid-session
            if (!Auth::user()->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'emp_id' => 'Your account has been deactivated. Please contact the administrator.',
                ]);
            }

            $request->session()->regenerate();
            record_login_attempt($credentials['emp_id'], true);
            audit_log('login', "User {$credentials['emp_id']} logged in");

            if (Auth::user()->must_change_password) {
                return redirect()->route('change-password.index')
                    ->with('error', 'You must change your password before continuing.');
            }

            return redirect()->intended(route('dashboard'));
        }

        record_login_attempt($credentials['emp_id'], false);

        $remaining = MAX_FAILED_ATTEMPTS - failed_login_attempts_in_window($credentials['emp_id']);
        $hint = $remaining > 0
            ? " You have {$remaining} attempt(s) remaining before your account is locked."
            : '';

        return back()->withErrors([
            'emp_id' => 'Invalid Employee ID, Department, or Password.' . $hint,
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
