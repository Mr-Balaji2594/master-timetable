<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSubject;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StaffSubjectController extends Controller
{
    private function canAssign(): bool
    {
        $user = Auth::user();
        return $user->isAdmin() || $user->isHOD();
    }

    public function index()
    {
        $user = Auth::user();

        $assignments = EmployeeSubject::with(['employee', 'subject'])
            ->when(!$user->isAdmin() && !$user->isPrincipal() && !$user->isVicePrincipal(), fn($q) => $q->whereHas('employee', fn($q) => $q->where('department_id', $user->department_id)))
            ->orderBy('employee_id')
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'employee_id' => $a->employee_id,
                'subject_id' => $a->subject_id,
                'employee' => $a->employee ? ['id' => $a->employee->id, 'emp_id' => $a->employee->emp_id, 'name' => $a->employee->name] : null,
                'subject' => $a->subject ? ['id' => $a->subject->id, 'name' => $a->subject->name, 'code' => $a->subject->code] : null,
            ]);

        $employees = Employee::where('is_active', true)
            ->when(!$user->isAdmin() && !$user->isPrincipal() && !$user->isVicePrincipal(), fn($q) => $q->where('department_id', $user->department_id))
            ->orderBy('name')
            ->get(['id', 'emp_id', 'name', 'department_id']);

        $subjects = Subject::with('department')
            ->when(!$user->isAdmin() && !$user->isPrincipal() && !$user->isVicePrincipal() && !$user->isHOD(), fn($q) => $q->where('department_id', $user->department_id))
            ->orderBy('department_id')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'department_id']);

        $departments = Department::orderBy('name')->get(['id', 'name']);

        return Inertia::render('StaffSubjects/Index', [
            'assignments' => $assignments,
            'employees' => $employees,
            'subjects' => $subjects,
            'departments' => $departments,
        ]);
    }

    public function store()
    {
        $user = Auth::user();
        if (!$this->canAssign()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $data = request()->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'integer|exists:subjects,id',
        ]);

        $created = 0;
        foreach ($data['subject_ids'] as $subjectId) {
            $exists = EmployeeSubject::where('employee_id', $data['employee_id'])
                ->where('subject_id', $subjectId)
                ->exists();

            if (!$exists) {
                EmployeeSubject::create([
                    'employee_id' => $data['employee_id'],
                    'subject_id' => $subjectId,
                ]);
                $created++;
            }
        }

        if ($created > 0) {
            audit_log('staff_subject_assign', "Assigned {$created} subject(s) to employee #{$data['employee_id']}");
            return redirect()->back()->with('success', "{$created} subject(s) assigned successfully");
        }

        return redirect()->back()->with('error', 'All selected subjects are already assigned');
    }

    public function destroy(EmployeeSubject $staffSubject)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isHOD()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $staffSubject->delete();
        audit_log('staff_subject_remove', "Removed subject assignment #{$staffSubject->id}");

        return redirect()->back()->with('success', 'Subject assignment removed successfully');
    }
}
