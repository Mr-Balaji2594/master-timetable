<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Rules\StrongPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    private function canModify(): bool
    {
        $user = Auth::user();
        return $user->isAdmin() || $user->isPrincipal() || $user->isVicePrincipal();
    }

    public function index()
    {
        $user = Auth::user();

        $employees = Employee::with('department')
            ->when(request('department_id'), fn($q, $v) => $q->where('department_id', $v))
            ->when(!$user->isAdmin() && !$user->isPrincipal() && !$user->isVicePrincipal(), fn($q) => $q->where('department_id', $user->department_id))
            ->orderBy('emp_id')
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'emp_id' => $e->emp_id,
                'department_id' => $e->department_id,
                'name' => $e->name,
                'designation' => $e->designation,
                'mode' => $e->mode ?? 'permanent',
                'role' => $e->role,

                'is_active' => $e->is_active,
                'department' => $e->department ? ['id' => $e->department->id, 'name' => $e->department->name] : null,
            ]);

        $departments = Department::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'departments' => $departments,
            'filters' => request()->only(['department_id']),
        ]);
    }

    public function store()
    {
        if (!$this->canModify()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $data = request()->validate([
            'emp_id' => 'required|string|max:50|unique:employees,emp_id',
            'department_id' => 'required|integer|exists:departments,id',
            'name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'mode' => 'nullable|string|in:permanent,temporary,contract',
            'role' => 'required|string|in:super_admin,admin,principal,vice_principal,hod,staff',
            'password' => ['required', 'string', new StrongPassword],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = true;
        $data['mode'] = $data['mode'] ?? 'permanent';
        $data['must_change_password'] = true;

        Employee::create($data);
        audit_log('employee_create', "Created employee: {$data['emp_id']} - {$data['name']}");

        return redirect()->back()->with('success', 'Employee created successfully');
    }

    public function update(Employee $employee)
    {
        if (!$this->canModify()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $data = request()->validate([
            'emp_id' => 'required|string|max:50|unique:employees,emp_id,' . $employee->id,
            'department_id' => 'required|integer|exists:departments,id',
            'name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'mode' => 'nullable|string|in:permanent,temporary,contract',
            'role' => 'required|string|in:super_admin,admin,principal,vice_principal,hod,staff',
            'is_active' => 'boolean',
        ]);

        if (request('password')) {
            request()->validate(['password' => ['string', new StrongPassword]]);
            $data['password'] = Hash::make(request('password'));
            $data['must_change_password'] = true;
        }

        $employee->update($data);
        audit_log('employee_update', "Updated employee: {$data['emp_id']} - {$data['name']}");

        return redirect()->back()->with('success', 'Employee updated successfully');
    }

    public function destroy(Employee $employee)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isPrincipal()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $employee->delete();
        audit_log('employee_delete', "Deleted employee: {$employee->emp_id} - {$employee->name}");

        return redirect()->back()->with('success', 'Employee deleted successfully');
    }

    public function resetPassword(Employee $employee)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isPrincipal()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $tempPassword = generate_temp_password();
        $employee->update([
            'password' => Hash::make($tempPassword),
            'must_change_password' => true,
        ]);
        audit_log('employee_password_reset', "Reset password for employee: {$employee->emp_id}");

        return redirect()->back()->with('success', "Temporary password set for {$employee->emp_id}: {$tempPassword}");
    }

    public function toggleStatus(Employee $employee)
    {
        if (!$this->canModify()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        // Prevent deactivating your own account
        if ($employee->id === Auth::id()) {
            return redirect()->back()->with('error', 'You cannot change your own account status.');
        }

        $newStatus = !$employee->is_active;
        $employee->update(['is_active' => $newStatus]);

        $statusLabel = $newStatus ? 'activated' : 'deactivated';
        audit_log('employee_status_change', "Employee {$employee->emp_id} ({$employee->name}) {$statusLabel}");

        return redirect()->back()->with('success', "Employee {$employee->name} has been {$statusLabel}.");
    }
}
