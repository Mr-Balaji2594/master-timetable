<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LeaveController extends Controller
{
    private function hoursBetween(string $start, string $end): float
    {
        [$sh, $sm] = array_map('intval', explode(':', $start));
        [$eh, $em] = array_map('intval', explode(':', $end));
        $minutes = ($eh * 60 + $em) - ($sh * 60 + $sm);
        return $minutes > 0 ? round($minutes / 60, 1) : 0;
    }

    public function index()
    {
        $user = Auth::user();

        $leaves = LeaveRequest::with(['employee', 'hodApprover', 'principalApprover'])
            ->when($user->isStaff(), fn($q) => $q->where('employee_id', $user->id))
            ->when($user->isHOD(), fn($q) => $q->where(function ($q) use ($user) {
                $q->where('employee_id', $user->id)
                    ->orWhere(function ($w) use ($user) {
                        $w->whereHas('employee', fn($e) => $e->where('department_id', $user->department_id))
                            ->where(fn($s) => $s->whereNull('status')->orWhere('status', 'pending_hod'));
                    });
            }))
            ->when($user->isPrincipal(), fn($q) => $q->where(function ($q) use ($user) {
                $q->where('employee_id', $user->id)
                    ->orWhere('status', 'pending_principal');
            }))
            ->orderByDesc('leave_date')
            ->get()
            ->map(fn($l) => [
                'id' => $l->id,
                'employee_id' => $l->employee_id,
                'leave_date' => $l->leave_date?->format('Y-m-d'),
                'due_date' => $l->due_date?->format('Y-m-d'),
                'start_time' => $l->start_time,
                'due_time' => $l->due_time,
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
            'early_permission_limit', 'early_permission_availed',
            'late_permission_limit', 'late_permission_availed',
            'deputation_limit', 'deputation_availed',
            'earned_leave_limit', 'earned_leave_availed',
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

        $data = $this->validatedLeaveData();
        $data['employee_id'] = $user->id;
        $data['status'] = ($user->isHOD() || $user->isPrincipal()) ? 'pending_principal' : 'pending_hod';

        LeaveRequest::create($data);
        $timeDetail = $data['start_time'] ? " from {$data['start_time']} to {$data['due_time']}" : '';
        audit_log('leave_apply', "Applied for leave: {$data['nature']} from {$data['leave_date']} to {$data['due_date']}{$timeDetail}");

        return redirect()->back()->with('success', 'Leave applied successfully');
    }

    public function update(LeaveRequest $leave)
    {
        $user = Auth::user();

        if ($leave->employee_id !== $user->id && !$user->isAdmin() && !$user->isPrincipal()) {
            return redirect()->back()->with('error', 'You can only edit your own leave requests');
        }

        if (in_array($leave->status, ['approved', 'rejected'])) {
            return redirect()->back()->with('error', 'Cannot edit a leave request that is already ' . $leave->status);
        }

        if ($user->isStaff() && ($leave->status ?: 'pending_hod') !== 'pending_hod') {
            return redirect()->back()->with('error', 'Leave can only be edited while pending HOD approval');
        }

        $data = $this->validatedLeaveData();
        $leave->update($data);

        audit_log('leave_update', "Updated leave #{$leave->id} for employee #{$leave->employee_id}");

        return redirect()->back()->with('success', 'Leave request updated successfully');
    }

    public function destroy(LeaveRequest $leave)
    {
        $user = Auth::user();

        if ($leave->employee_id !== $user->id && !$user->isAdmin() && !$user->isPrincipal()) {
            return redirect()->back()->with('error', 'You can only delete your own leave requests');
        }

        if (in_array($leave->status, ['approved', 'rejected'])) {
            return redirect()->back()->with('error', 'Cannot delete a leave request that is already ' . $leave->status);
        }

        if ($user->isStaff() && ($leave->status ?: 'pending_hod') !== 'pending_hod') {
            return redirect()->back()->with('error', 'Leave can only be deleted while pending HOD approval');
        }

        $leave->delete();
        audit_log('leave_delete', "Deleted leave #{$leave->id} for employee #{$leave->employee_id}");

        return redirect()->back()->with('success', 'Leave request deleted successfully');
    }

    private function validatedLeaveData(): array
    {
        $data = request()->validate([
            'leave_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:leave_date',
            'start_time' => 'nullable|date_format:H:i',
            'due_time' => 'nullable|date_format:H:i',
            'nature' => 'required|string|max:100',
            'days' => 'required|numeric|min:0|max:30',
            'reason' => 'required|string',
        ]);

        if (in_array($data['nature'], ['early_permission', 'late_permission'])) {
            if (empty($data['start_time']) || empty($data['due_time'])) {
                throw ValidationException::withMessages(['start_time' => 'Start time and due time are required for permission requests']);
            }
            if ($data['due_time'] <= $data['start_time']) {
                throw ValidationException::withMessages(['due_time' => 'Due time must be after start time']);
            }
            $data['days'] = $this->hoursBetween($data['start_time'], $data['due_time']);
            $data['due_date'] = $data['leave_date'];
        } else {
            if (empty($data['due_date'])) {
                throw ValidationException::withMessages(['due_date' => 'Due date is required']);
            }
            $data['start_time'] = null;
            $data['due_time'] = null;
        }

        return $data;
    }

    private function availedColumn(string $nature): ?string
    {
        return [
            'casual' => 'casual_leave_availed',
            'medical' => 'medical_leave_availed',
            'onduty' => 'onduty_leave_availed',
            'early_permission' => 'early_permission_availed',
            'late_permission' => 'late_permission_availed',
            'deputation' => 'deputation_availed',
            'earned' => 'earned_leave_availed',
        ][$nature] ?? null;
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

        $column = $this->availedColumn($leave->nature);

        if ($column !== null) {
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

    private function eligibleBulkQuery(array $ids)
    {
        $user = Auth::user();
        $query = LeaveRequest::whereIn('id', $ids);

        if (!$user->isAdmin() && !$user->isPrincipal()) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', $user->department_id));
        }

        return $query;
    }

    public function bulkApproveHod()
    {
        $user = Auth::user();
        if (!$user->isHOD() && !$user->isPrincipal() && !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $ids = request()->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];

        $leaves = $this->eligibleBulkQuery($ids)
            ->where(fn($q) => $q->whereNull('status')->orWhere('status', 'pending_hod'))
            ->get();

        if ($leaves->isEmpty()) {
            return redirect()->back()->with('error', 'No pending requests found to forward');
        }

        $now = now();
        foreach ($leaves as $leave) {
            $leave->update([
                'status' => 'pending_principal',
                'hod_approved_by' => $user->id,
                'hod_approved_at' => $now,
            ]);
        }

        audit_log('leave_bulk_hod_approve', "Bulk forwarded {$leaves->count()} leave requests to principal");

        return redirect()->back()->with('success', "Forwarded {$leaves->count()} leave request(s) to Principal");
    }

    public function bulkApprovePrincipal()
    {
        $user = Auth::user();
        if (!$user->isPrincipal() && !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $ids = request()->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];

        $leaves = $this->eligibleBulkQuery($ids)
            ->whereIn('status', ['pending_hod', 'pending_principal'])
            ->get();

        if ($leaves->isEmpty()) {
            return redirect()->back()->with('error', 'No pending requests found to approve');
        }

        foreach ($leaves as $leave) {
            $leave->update([
                'status' => 'approved',
                'principal_approved_by' => $user->id,
                'principal_approved_at' => now(),
            ]);

            $column = $this->availedColumn($leave->nature);
            if ($column !== null) {
                Employee::where('id', $leave->employee_id)->increment($column, $leave->days);
            }
        }

        audit_log('leave_bulk_principal_approve', "Bulk approved {$leaves->count()} leave requests");

        return redirect()->back()->with('success', "Approved {$leaves->count()} leave request(s)");
    }

    public function bulkReject()
    {
        $user = Auth::user();
        if (!$user->isHOD() && !$user->isPrincipal() && !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $ids = request()->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];

        $leaves = $this->eligibleBulkQuery($ids)
            ->whereNotIn('status', ['approved', 'rejected'])
            ->get();

        if ($leaves->isEmpty()) {
            return redirect()->back()->with('error', 'No pending requests found to reject');
        }

        foreach ($leaves as $leave) {
            $leave->update(['status' => 'rejected']);
        }

        audit_log('leave_bulk_reject', "Bulk rejected {$leaves->count()} leave requests");

        return redirect()->back()->with('success', "Rejected {$leaves->count()} leave request(s)");
    }
}
