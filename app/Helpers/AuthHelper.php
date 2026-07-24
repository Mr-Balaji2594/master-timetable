<?php

use App\Models\AuditLog;
use App\Models\LoginAttempt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

const LOGIN_LOCKOUT_MINUTES = 15;

function audit_log(string $action, string $details = ''): void
{
    $user = Auth::user();
    AuditLog::create([
        'user_id' => $user?->id ?? 0,
        'emp_id' => $user?->emp_id ?? '',
        'action' => $action,
        'details' => substr($details, 0, 500),
        'ip_address' => Request::ip(),
        'user_agent' => substr((string) Request::userAgent(), 0, 255),
    ]);
}

function check_login_attempts(string $emp_id): bool
{
    $cutoff = now()->subMinutes(LOGIN_LOCKOUT_MINUTES);
    $count = LoginAttempt::where('emp_id', $emp_id)
        ->where('attempted_at', '>', $cutoff)
        ->where('success', false)
        ->count();

    return $count < 5;
}

function record_login_attempt(string $emp_id, bool $success): void
{
    LoginAttempt::create([
        'emp_id' => $emp_id,
        'ip_address' => Request::ip(),
        'success' => $success,
    ]);
}
