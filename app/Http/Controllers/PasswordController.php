<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class PasswordController extends Controller
{
    public function index()
    {
        return Inertia::render('Auth/ChangePassword');
    }

    public function update()
    {
        $user = Auth::user();

        $data = request()->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        $user->update(['password' => Hash::make($data['new_password'])]);
        audit_log('password_change', "Password changed for user: {$user->emp_id}");

        return redirect()->back()->with('success', 'Password changed successfully');
    }
}
