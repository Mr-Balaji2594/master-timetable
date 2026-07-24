<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LeaveController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $leaves = LeaveRequest::with(['employee', 'hodApprover', 'principalApprover'])
            ->when($user->isStaff(), fn($q) => $q->where('employee_id', $user->id))
            ->when($user->isHOD(), fn($q) => $q->whereHas('employee', fn($q) => $q->where('department_id', $user->department_id)))
            ->orderByDesc('leave_date')
            ->get()
            ->map(fn($l) => [
                'id' => $l->id,
                'employee_id' => $l->employee_id,
                'leave_date' => $l->leave_date,
                'due_date' => $l->due_date,
                'nature' => $l->nature,
                'days' => $l->days,
                'reason' => $l->reason,
                'status' => $l->status,
                'hod_approved_by' => $l->hod_approved_by,
                'hod_approved_at' => $l->hod_approved_at?->format('d/m/Y H:i:s'),
                'principal_approved_by' => $l->principal_approved_by,
                'principal_approved_at' => $l->principal_approved_at?->format('d/m/Y H:i:s'),
                'employee' => $l->employee ? ['id' => $l->employee->id, 'emp_id' => $l->employee->emp_id, 'name' => $l->employee->name, 'department_id' => $l->employee->department_id] : null,
                'hod_approver' => $l->hodApprover ? ['id' => $l->hodApprover->id, 'name' => $l->hodApprover->name] : null,
                'principal_approver' => $l->principalApprover ? ['id' => $l->principalApprover->id, 'name' => $l->principalApprover->name] : null,
            ]);

        $employees = Employee::where('is_active', true)
            ->when($user->isStaff(), fn($q) => $q->where('id', $user->id))
            ->when($user->isHOD(), fn($q) => $q->where('department_id', $user->department_id))
            ->orderBy('name')
            ->get(['id', 'emp_id', 'name']);

        $leaveBalance = $user->only([
            'casual_leave_limit', 'casual_leave_availed',
            'medical_leave_limit', 'medical_leave_availed',
            'onduty_leave_limit', 'onduty_leave_availed',
            'permission_limit', 'permission_availed',
            'deputation_limit', 'deputation_availed',
        ]);

        return Inertia::render('Leave/Index', [
            'leaves' => $leaves,
            'employees' => $employees,
            'leaveBalance' => $leaveBalance,
        ]);
    }

    public function store()
    {
        $user = Auth::user();

        $data = request()->validate([
            'leave_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:leave_date',
            'nature' => 'required|string|max:100',
            'days' => 'required|integer|min:1',
            'reason' => 'required|string',
        ]);

        $data['employee_id'] = $user->id;
        $data['status'] = $user->isHOD() ? 'pending_principal' : 'pending_hod';

        LeaveRequest::create($data);
        audit_log('leave_apply', "Applied for leave: {$data['nature']} from {$data['leave_date']} to {$data['due_date']}");

        return redirect()->back()->with('success', 'Leave applied successfully');
    }

    public function approveHod(LeaveRequest $leave)
    {
        $user = Auth::user();
        if (!$user->isHOD() && !$user->isPrincipal() && !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $status = $leave->status ?: 'pending_hod';

        if ($status !== 'pending_hod') {
            return redirect()->back()->with('error', 'Leave is already ' . $status);
        }

        $leave->update([
            'status' => 'pending_principal',
            'hod_approved_by' => $user->id,
            'hod_approved_at' => now(),
        ]);

        audit_log('leave_hod_approve', "HOD approved leave #{$leave->id} for employee #{$leave->employee_id}");

        return redirect()->back()->with('success', 'Leave approved by HOD');
    }

    public function approvePrincipal(LeaveRequest $leave)
    {
        $user = Auth::user();
        if (!$user->isPrincipal() && !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $status = $leave->status ?: 'pending_hod';

        if (!in_array($status, ['pending_hod', 'pending_principal'])) {
            return redirect()->back()->with('error', 'Leave cannot be approved at this stage. Current status: ' . $status);
        }

        $leave->update([
            'status' => 'approved',
            'principal_approved_by' => $user->id,
            'principal_approved_at' => now(),
        ]);

        $availedMap = [
            'casual' => 'casual_leave_availed',
            'medical' => 'medical_leave_availed',
            'onduty' => 'onduty_leave_availed',
            'permission' => 'permission_availed',
            'deputation' => 'deputation_availed',
        ];

        if (isset($availedMap[$leave->nature])) {
            $column = $availedMap[$leave->nature];
            Employee::where('id', $leave->employee_id)->increment($column, $leave->days);
        }

        audit_log('leave_principal_approve', "Principal approved leave #{$leave->id} for employee #{$leave->employee_id}");

        return redirect()->back()->with('success', 'Leave approved by Principal');
    }

    public function reject(LeaveRequest $leave)
    {
        $user = Auth::user();
        if (!$user->isHOD() && !$user->isPrincipal() && !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $status = $leave->status ?: 'pending_hod';

        if ($status === 'rejected' || $status === 'approved') {
            return redirect()->back()->with('error', 'Leave cannot be rejected at this stage');
        }

        $leave->update(['status' => 'rejected']);
        audit_log('leave_reject', "Rejected leave #{$leave->id} for employee #{$leave->employee_id}");

        return redirect()->back()->with('success', 'Leave rejected');
    }
}
