<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
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
            ->orderBy('name')
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'emp_id' => $e->emp_id,
                'department_id' => $e->department_id,
                'name' => $e->name,
                'designation' => $e->designation,
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
            'role' => 'required|string|in:super_admin,admin,principal,vice_principal,hod,staff',
            'password' => 'required|string|min:6',
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = true;

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
            'role' => 'required|string|in:super_admin,admin,principal,vice_principal,hod,staff',
            'is_active' => 'boolean',
        ]);

        if (request('password')) {
            request()->validate(['password' => 'string|min:6']);
            $data['password'] = Hash::make(request('password'));
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

        $defaultPassword = 'password123';
        $employee->update(['password' => Hash::make($defaultPassword)]);
        audit_log('employee_password_reset', "Reset password for employee: {$employee->emp_id}");

        return redirect()->back()->with('success', "Password reset to: {$defaultPassword}");
    }
}
