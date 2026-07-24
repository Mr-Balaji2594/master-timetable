<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TimetableSlot;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TimetableController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $isScoped = !$user->isAdmin() && !$user->isPrincipal() && !$user->isVicePrincipal();
        $isHodScoped = $isScoped || $user->isHOD();

        $deptScope = fn($q) => $q->where('department_id', $user->department_id);

        $slots = TimetableSlot::with(['class.department', 'subject', 'employee'])
            ->when($isHodScoped, fn($q) => $q->whereHas('employee', $deptScope))
            ->when(request('employee_id'), fn($q, $v) => $q->where('employee_id', $v))
            ->when(request('class_id'), fn($q, $v) => $q->where('class_id', $v))
            ->when(request('day_of_week'), fn($q, $v) => $q->where('day_of_week', $v))
            ->orderBy('day_of_week')
            ->orderBy('period_no')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'class_id' => $s->class_id,
                'subject_id' => $s->subject_id,
                'employee_id' => $s->employee_id,
                'day_of_week' => $s->day_of_week,
                'period_no' => $s->period_no,
                'semester' => $s->semester,
                'combined_group_id' => $s->combined_group_id,
                'class' => $s->class ? ['id' => $s->class->id, 'name' => $s->class->name, 'year' => $s->class->year, 'dept_code' => $s->class->department?->code] : null,
                'subject' => $s->subject ? ['id' => $s->subject->id, 'name' => $s->subject->name, 'code' => $s->subject->code] : null,
                'employee' => $s->employee ? ['id' => $s->employee->id, 'emp_id' => $s->employee->emp_id, 'name' => $s->employee->name] : null,
            ]);

        $classes = SchoolClass::with('department')
            ->when($isHodScoped, $deptScope)
            ->orderBy('name')
            ->get(['id', 'name', 'department_id', 'year'])
            ->map(fn($c) => [
                'id' => $c->id,
                'label' => "{$c->name} - {$c->department?->name} - {$c->year}",
            ]);

        $employees = Employee::where('is_active', true)
            ->when($isHodScoped, fn($q) => $q->where('department_id', $user->department_id))
            ->orderBy('name')
            ->get(['id', 'emp_id', 'name', 'department_id']);

        $subjects = Subject::when($isHodScoped, $deptScope)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('Timetable/Index', [
            'slots' => $slots,
            'employees' => $employees,
            'classes' => $classes,
            'subjects' => $subjects,
            'filters' => request()->only(['employee_id', 'class_id', 'day_of_week']),
        ]);
    }

    public function store()
    {
        $data = request()->validate([
            'class_id' => 'required|integer|exists:classes,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'employee_id' => 'required|integer|exists:employees,id',
            'day_of_week' => 'required|integer|between:1,6',
            'period_no' => 'required|integer|min:1|max:5',
            'semester' => 'nullable|string|max:10',
            'combined_group_id' => 'nullable|integer',
        ]);

        TimetableSlot::create($data);
        audit_log('timetable_create', "Created timetable slot: Class #{$data['class_id']}, Period {$data['period_no']}, Day {$data['day_of_week']}");

        return redirect()->back()->with('success', 'Timetable slot created successfully');
    }

    public function destroy(TimetableSlot $slot)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isHOD() && !$user->isPrincipal()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $slot->delete();
        audit_log('timetable_delete', "Deleted timetable slot #{$slot->id}");

        return redirect()->back()->with('success', 'Timetable slot deleted successfully');
    }
}