<?php

use App\Models\AuditLog;
use App\Models\LoginAttempt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

const LOGIN_LOCKOUT_MINUTES = 15;
const MAX_FAILED_ATTEMPTS = 5;

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
    return failed_login_attempts_in_window($emp_id) < MAX_FAILED_ATTEMPTS;
}

function failed_login_attempts_in_window(string $emp_id): int
{
    $cutoff = now()->subMinutes(LOGIN_LOCKOUT_MINUTES);

    return LoginAttempt::where('emp_id', $emp_id)
        ->where('attempted_at', '>', $cutoff)
        ->where('success', false)
        ->count();
}

function login_lockout_remaining_seconds(string $emp_id): int
{
    $cutoff = now()->subMinutes(LOGIN_LOCKOUT_MINUTES);
    $latest = LoginAttempt::where('emp_id', $emp_id)
        ->where('attempted_at', '>', $cutoff)
        ->where('success', false)
        ->orderByDesc('attempted_at')
        ->first();

    if (!$latest) {
        return 0;
    }

    return max(0, (int) $latest->attempted_at->addMinutes(LOGIN_LOCKOUT_MINUTES)->diffInSeconds(now()));
}

function generate_temp_password(int $length = 10): string
{
    $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lower = 'abcdefghjkmnpqrstuvwxyz';
    $digits = '23456789';
    $symbols = '!@#$%';

    $chars = str_shuffle($upper) . str_shuffle($lower) . str_shuffle($digits) . $symbols;
    $password = '';
    $password .= $upper[random_int(0, strlen($upper) - 1)];
    $password .= $lower[random_int(0, strlen($lower) - 1)];
    $password .= $digits[random_int(0, strlen($digits) - 1)];
    $password .= $symbols[random_int(0, strlen($symbols) - 1)];

    for ($i = 4; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }

    return str_shuffle($password);
}

function record_login_attempt(string $emp_id, bool $success): void
{
    LoginAttempt::create([
        'emp_id' => $emp_id,
        'ip_address' => Request::ip(),
        'success' => $success,
        'user_agent' => substr((string) Request::userAgent(), 0, 255),
    ]);
}
