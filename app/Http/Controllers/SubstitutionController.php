<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\SubstitutionDuty;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SubstitutionController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $substitutions = SubstitutionDuty::with(['originalEmployee', 'substituteEmployee', 'class', 'subject'])
            ->when($user->isHOD(), fn($q) => $q->whereHas('originalEmployee', fn($q) => $q->where('department_id', $user->department_id)))
            ->when($user->isStaff(), fn($q) => $q->where('substitute_employee_id', $user->id)->orWhere('original_employee_id', $user->id))
            ->orderByDesc('leave_date')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'original_employee_id' => $s->original_employee_id,
                'substitute_employee_id' => $s->substitute_employee_id,
                'class_id' => $s->class_id,
                'subject_id' => $s->subject_id,
                'day_of_week' => $s->day_of_week,
                'period_no' => $s->period_no,
                'leave_date' => $s->leave_date,
                'status' => $s->status,
                'compensation_hours' => $s->compensation_hours,
                'original_employee' => $s->originalEmployee ? ['id' => $s->originalEmployee->id, 'name' => $s->originalEmployee->name] : null,
                'substitute_employee' => $s->substituteEmployee ? ['id' => $s->substituteEmployee->id, 'name' => $s->substituteEmployee->name] : null,
                'class' => $s->class ? ['id' => $s->class->id, 'name' => $s->class->name] : null,
                'subject' => $s->subject ? ['id' => $s->subject->id, 'name' => $s->subject->name] : null,
            ]);

        $classes = SchoolClass::orderBy('name')->get(['id', 'name']);
        $subjects = Subject::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Substitution/Index', [
            'substitutions' => $substitutions,
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
            'compensation_hours' => 'nullable|numeric|min:0',
        ]);

        $data['status'] = 'pending';

        SubstitutionDuty::create($data);
        audit_log('substitution_create', "Created substitution for employee #{$data['original_employee_id']} on {$data['leave_date']}");

        return redirect()->back()->with('success', 'Substitution duty created successfully');
    }

    public function update(SubstitutionDuty $substitution)
    {
        $data = request()->validate([
            'status' => 'required|string|in:pending,completed,cancelled',
            'compensation_hours' => 'nullable|numeric|min:0',
        ]);

        $substitution->update($data);
        audit_log('substitution_update', "Updated substitution #{$substitution->id} status to {$data['status']}");

        return redirect()->back()->with('success', 'Substitution duty updated successfully');
    }

    public function destroy(SubstitutionDuty $substitution)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isHOD() && !$user->isPrincipal()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $substitution->delete();
        audit_log('substitution_delete', "Deleted substitution #{$substitution->id}");

        return redirect()->back()->with('success', 'Substitution duty deleted successfully');
    }
}
