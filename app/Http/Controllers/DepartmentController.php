<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $departments = Department::withCount(['employees', 'subjects'])
            ->when(!$user->isAdmin() && !$user->isPrincipal() && !$user->isVicePrincipal(), fn($q) => $q->where('id', $user->department_id))
            ->orderBy('name')
            ->get()
            ->map(fn($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'hod_id' => $d->hod_id,
                'branch_code' => $d->branch_code,
                'staff_count' => $d->staff_count,
                'employees_count' => $d->employees_count,
                'subjects_count' => $d->subjects_count,
                'hod' => $d->hod ? ['id' => $d->hod->id, 'name' => $d->hod->name] : null,
            ]);

        $employees = Employee::select('id', 'name')
            ->where('role', 'hod')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Departments/Index', [
            'departments' => $departments,
            'employees' => $employees,
        ]);
    }

    public function store()
    {
        $data = request()->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments,code',
            'hod_id' => 'nullable|integer|exists:employees,id',
            'branch_code' => 'nullable|string|max:50',
            'staff_count' => 'nullable|integer|min:0',
        ]);

        Department::create($data);
        audit_log('department_create', "Created department: {$data['name']}");

        return redirect()->back()->with('success', 'Department created successfully');
    }

    public function update(Department $department)
    {
        $data = request()->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments,code,' . $department->id,
            'hod_id' => 'nullable|integer|exists:employees,id',
            'branch_code' => 'nullable|string|max:50',
            'staff_count' => 'nullable|integer|min:0',
        ]);

        $department->update($data);
        audit_log('department_update', "Updated department: {$data['name']}");

        return redirect()->back()->with('success', 'Department updated successfully');
    }

    public function destroy(Department $department)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isPrincipal()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $department->delete();
        audit_log('department_delete', "Deleted department: {$department->name}");

        return redirect()->back()->with('success', 'Department deleted successfully');
    }
}
