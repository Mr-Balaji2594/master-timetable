<?php

namespace App\Http\Controllers;

use App\Models\Compensation;
use App\Models\Employee;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CompensationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $compensations = Compensation::with(['originalEmployee', 'substituteEmployee', 'class', 'subject'])
            ->when($user->isStaff(), fn($q) => $q->where('original_employee_id', $user->id)->orWhere('substitute_employee_id', $user->id))
            ->when($user->isHOD(), fn($q) => $q->where(function($q) use ($user) {
                $q->whereHas('originalEmployee', fn($q) => $q->where('department_id', $user->department_id))
                  ->orWhere('substitute_employee_id', $user->id)
                  ->orWhere('original_employee_id', $user->id);
            }))
            ->orderByDesc('leave_date')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'original_employee_id' => $c->original_employee_id,
                'substitute_employee_id' => $c->substitute_employee_id,
                'class_id' => $c->class_id,
                'subject_id' => $c->subject_id,
                'day_of_week' => $c->day_of_week,
                'period_no' => $c->period_no,
                'leave_date' => $c->leave_date,
                'compensation_date' => $c->compensation_date,
                'compensation_period' => $c->compensation_period,
                'status' => $c->status,
                'original_employee' => $c->originalEmployee ? ['id' => $c->originalEmployee->id, 'name' => $c->originalEmployee->name] : null,
                'substitute_employee' => $c->substituteEmployee ? ['id' => $c->substituteEmployee->id, 'name' => $c->substituteEmployee->name] : null,
                'class' => $c->class ? ['id' => $c->class->id, 'name' => $c->class->name] : null,
                'subject' => $c->subject ? ['id' => $c->subject->id, 'name' => $c->subject->name] : null,
            ]);

        $classes = SchoolClass::with('department')->orderBy('name')->get(['id', 'name', 'department_id', 'year']);
        $subjects = Subject::orderBy('name')->get(['id', 'name']);
        $employees = Employee::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Compensation/Index', [
            'compensations' => $compensations,
            'employees' => $employees,
            'classes' => $classes,
            'subjects' => $subjects,
        ]);
    }

    public function store()
    {
        $data = request()->validate([
            'original_employee_id' => 'required|integer|exists:employees,id',
            'substitute_employee_id' => 'required|integer|exists:employees,id|different:original_employee_id',
            'class_id' => 'required|integer|exists:classes,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'day_of_week' => 'required|integer|between:1,6',
            'period_no' => 'required|integer|min:1',
            'leave_date' => 'required|date',
        ]);

        $data['status'] = 'pending';

        Compensation::create($data);
        audit_log('compensation_create', "Created compensation for substitution leave on {$data['leave_date']}");

        return redirect()->back()->with('success', 'Compensation record created successfully');
    }

    public function complete(Compensation $compensation)
    {
        if ($compensation->status !== 'pending') {
            return redirect()->back()->with('error', 'Compensation is already ' . $compensation->status);
        }

        $compensation->update(['status' => 'completed']);
        audit_log('compensation_complete', "Completed compensation #{$compensation->id}");

        return redirect()->back()->with('success', 'Compensation completed by substitute');
    }

    public function approve(Compensation $compensation)
    {
        if ($compensation->status !== 'completed') {
            return redirect()->back()->with('error', 'Compensation must be completed before final approval');
        }

        $compensation->update(['status' => 'approved']);
        audit_log('compensation_approve', "Final approved compensation #{$compensation->id}");

        return redirect()->back()->with('success', 'Compensation final approved successfully');
    }

    public function cancel(Compensation $compensation)
    {
        if (in_array($compensation->status, ['completed', 'approved', 'cancelled'])) {
            return redirect()->back()->with('error', 'Compensation cannot be cancelled at current status');
        }

        $compensation->update(['status' => 'cancelled']);
        audit_log('compensation_cancel', "Cancelled compensation #{$compensation->id}");

        return redirect()->back()->with('success', 'Compensation cancelled');
    }
}