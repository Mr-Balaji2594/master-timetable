<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LessonPlan;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LessonPlanController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $plans = LessonPlan::with(['employee', 'class', 'subject', 'hodApprover', 'principalApprover'])
            ->when($user->isStaff(), fn($q) => $q->where('employee_id', $user->id))
            ->when($user->isHOD(), fn($q) => $q->whereHas('employee', fn($q) => $q->where('department_id', $user->department_id)))
            ->orderByDesc('plan_date')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'employee_id' => $p->employee_id,
                'class_id' => $p->class_id,
                'subject_id' => $p->subject_id,
                'day' => $p->day,
                'period' => $p->period,
                'semester' => $p->semester,
                'topic' => $p->topic,
                'description' => $p->description,
                'unit' => $p->unit,
                'plan_date' => $p->plan_date,
                'status' => $p->status,
                'hod_approved_by' => $p->hod_approved_by,
                'hod_approved_at' => $p->hod_approved_at?->format('d/m/Y H:i:s'),
                'principal_approved_by' => $p->principal_approved_by,
                'principal_approved_at' => $p->principal_approved_at?->format('d/m/Y H:i:s'),
                'employee' => $p->employee ? ['id' => $p->employee->id, 'emp_id' => $p->employee->emp_id, 'name' => $p->employee->name] : null,
                'class' => $p->class ? ['id' => $p->class->id, 'name' => $p->class->name] : null,
                'subject' => $p->subject ? ['id' => $p->subject->id, 'name' => $p->subject->name, 'code' => $p->subject->code] : null,
                'hod_approver' => $p->hodApprover ? ['id' => $p->hodApprover->id, 'name' => $p->hodApprover->name] : null,
                'principal_approver' => $p->principalApprover ? ['id' => $p->principalApprover->id, 'name' => $p->principalApprover->name] : null,
            ]);

        $classes = SchoolClass::orderBy('name')->get(['id', 'name']);
        $subjects = Subject::orderBy('name')->get(['id', 'name', 'code']);
        $employees = Employee::where('is_active', true)->orderBy('name')->get(['id', 'emp_id', 'name']);

        return Inertia::render('LessonPlan/Index', [
            'plans' => $plans,
            'classes' => $classes,
            'subjects' => $subjects,
            'employees' => $employees,
        ]);
    }

    public function store()
    {
        $user = Auth::user();

        $data = request()->validate([
            'class_id' => 'required|integer|exists:classes,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'day' => 'nullable|integer|between:1,6',
            'period' => 'nullable|integer|min:1',
            'semester' => 'nullable|string|max:10',
            'topic' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'nullable|string|max:255',
            'plan_date' => 'required|date',
        ]);

        $data['employee_id'] = $user->id;
        $data['status'] = 'pending_hod';

        LessonPlan::create($data);
        audit_log('lesson_plan_create', "Created lesson plan: {$data['topic']}");

        return redirect()->back()->with('success', 'Lesson plan created successfully');
    }

    public function approveHod(LessonPlan $lessonPlan)
    {
        $user = Auth::user();
        if (!$user->isHOD() && !$user->isPrincipal() && !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        if ($lessonPlan->status !== 'pending_hod') {
            return redirect()->back()->with('error', 'Lesson plan is already ' . $lessonPlan->status);
        }

        $lessonPlan->update([
            'status' => 'pending_principal',
            'hod_approved_by' => $user->id,
            'hod_approved_at' => now(),
        ]);

        audit_log('lesson_plan_hod_approve', "HOD approved lesson plan #{$lessonPlan->id}");

        return redirect()->back()->with('success', 'Lesson plan approved by HOD');
    }

    public function approvePrincipal(LessonPlan $lessonPlan)
    {
        $user = Auth::user();
        if (!$user->isPrincipal() && !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        if (!in_array($lessonPlan->status, ['pending_hod', 'pending_principal'])) {
            return redirect()->back()->with('error', 'Lesson plan cannot be approved at this stage');
        }

        $lessonPlan->update([
            'status' => 'approved',
            'principal_approved_by' => $user->id,
            'principal_approved_at' => now(),
        ]);

        audit_log('lesson_plan_principal_approve', "Principal approved lesson plan #{$lessonPlan->id}");

        return redirect()->back()->with('success', 'Lesson plan approved by Principal');
    }

    public function reject(LessonPlan $lessonPlan)
    {
        $user = Auth::user();
        if (!$user->isHOD() && !$user->isPrincipal() && !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        if (in_array($lessonPlan->status, ['rejected', 'approved'])) {
            return redirect()->back()->with('error', 'Lesson plan cannot be rejected at this stage');
        }

        $lessonPlan->update(['status' => 'rejected']);
        audit_log('lesson_plan_reject', "Rejected lesson plan #{$lessonPlan->id}");

        return redirect()->back()->with('success', 'Lesson plan rejected');
    }
}
