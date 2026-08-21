<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LoginAttempt;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginAttemptController extends Controller
{
    private function canManage(): bool
    {
        return Auth::user()->isAdmin();
    }

    public function index()
    {
        if (!$this->canManage()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $search = request('emp_id');
        $cutoff = now()->subMinutes(LOGIN_LOCKOUT_MINUTES);

        $attempts = LoginAttempt::where('success', false)
            ->where('attempted_at', '>=', now()->subHours(24))
            ->when($search, fn($q, $v) => $q->where('emp_id', 'like', "%{$v}%"))
            ->get()
            ->groupBy('emp_id')
            ->map(function ($rows) use ($cutoff) {
                $recent = $rows->filter(fn($r) => $r->attempted_at > $cutoff);

                return [
                    'emp_id' => $rows->first()->emp_id,
                    'employee_name' => Employee::where('emp_id', $rows->first()->emp_id)->value('name') ?? '—',
                    'recent_failures' => $recent->count(),
                    'total_failures' => $rows->count(),
                    'locked' => $recent->count() >= MAX_FAILED_ATTEMPTS,
                    'last_attempt' => $rows->max('attempted_at')->format('d/m/Y H:i:s'),
                ];
            })
            ->values()
            ->sortByDesc(fn($a) => [
                $a['locked'],
                strtotime(str_replace('/', '-', $a['last_attempt'])),
            ])
            ->values();

        return Inertia::render('LoginAttempts/Index', [
            'attempts' => $attempts,
            'filters' => request()->only(['emp_id']),
        ]);
    }

    public function reset(string $empId)
    {
        if (!$this->canManage()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        LoginAttempt::where('emp_id', $empId)->where('success', false)->delete();
        audit_log('login_attempts_reset', "Reset failed login attempts for employee {$empId}");

        return redirect()->back()->with('success', "Login attempts reset for {$empId}");
    }

    public function resetAll()
    {
        if (!$this->canManage()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        LoginAttempt::where('success', false)->delete();
        audit_log('login_attempts_reset', 'Reset all failed login attempts');

        return redirect()->back()->with('success', 'All failed login attempts reset');
    }
}
