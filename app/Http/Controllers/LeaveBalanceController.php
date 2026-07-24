<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LeaveBalanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $employees = Employee::when(!$user->isAdmin() && !$user->isPrincipal() && !$user->isVicePrincipal(), fn($q) => $q->where('department_id', $user->department_id))
            ->orderBy('name')
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'emp_id' => $e->emp_id,
                'name' => $e->name,
                'department_id' => $e->department_id,
                'total_leave_per_year' => $e->total_leave_per_year,
                'casual_leave_limit' => $e->casual_leave_limit,
                'medical_leave_limit' => $e->medical_leave_limit,
                'onduty_leave_limit' => $e->onduty_leave_limit,
                'permission_limit' => $e->permission_limit,
                'deputation_limit' => $e->deputation_limit,
                'casual_leave_availed' => $e->casual_leave_availed,
                'medical_leave_availed' => $e->medical_leave_availed,
                'onduty_leave_availed' => $e->onduty_leave_availed,
                'permission_availed' => $e->permission_availed,
                'deputation_availed' => $e->deputation_availed,
            ]);

        return Inertia::render('LeaveBalance/Index', ['employees' => $employees]);
    }

    public function update(Employee $employee)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isPrincipal() && !$user->isHOD()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $data = request()->validate([
            'total_leave_per_year' => 'nullable|integer|min:0',
            'casual_leave_limit' => 'nullable|integer|min:0',
            'medical_leave_limit' => 'nullable|integer|min:0',
            'onduty_leave_limit' => 'nullable|integer|min:0',
            'permission_limit' => 'nullable|integer|min:0',
            'deputation_limit' => 'nullable|integer|min:0',
            'casual_leave_availed' => 'nullable|integer|min:0',
            'medical_leave_availed' => 'nullable|integer|min:0',
            'onduty_leave_availed' => 'nullable|integer|min:0',
            'permission_availed' => 'nullable|integer|min:0',
            'deputation_availed' => 'nullable|integer|min:0',
        ]);

        $employee->update($data);
        audit_log('leave_balance_update', "Updated leave balance for employee {$employee->emp_id}");

        return redirect()->back()->with('success', 'Leave balance updated successfully');
    }

    public function reset()
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isPrincipal()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        Employee::query()->update([
            'casual_leave_availed' => 0,
            'medical_leave_availed' => 0,
            'onduty_leave_availed' => 0,
            'permission_availed' => 0,
            'deputation_availed' => 0,
        ]);

        audit_log('leave_balance_reset', 'Reset all leave balances for new year');

        return redirect()->back()->with('success', 'All leave balances reset successfully');
    }
}
