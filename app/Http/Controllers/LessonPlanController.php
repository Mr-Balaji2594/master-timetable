<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LessonPlan;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LessonPlanController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $plans = LessonPlan::with(['employee', 'class.department', 'subject.department', 'hodApprover', 'principalApprover', 'combinedClasses.department'])
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
            ->orderByDesc('plan_date')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'employee_id' => $p->employee_id,
                'class_id' => $p->class_id,
                'subject_id' => $p->subject_id,
                'day' => $p->day,
                'period' => $p->period,
                'period_to' => $p->period_to,
                'semester' => $p->semester,
                'topic' => $p->topic,
                'description' => $p->description,
                'unit' => $p->unit,
                'plan_date' => $p->plan_date?->format('Y-m-d'),
                'status' => $p->status,
                'hod_approved_by' => $p->hod_approved_by,
                'hod_approved_at' => $p->hod_approved_at?->format('d/m/Y H:i:s'),
                'principal_approved_by' => $p->principal_approved_by,
                'principal_approved_at' => $p->principal_approved_at?->format('d/m/Y H:i:s'),
                'employee' => $p->employee ? ['id' => $p->employee->id, 'emp_id' => $p->employee->emp_id, 'name' => $p->employee->name] : null,
                'class' => $p->class ? ['id' => $p->class->id, 'name' => $p->class->name, 'department' => $p->class->department ? ['name' => $p->class->department->name] : null, 'year' => $p->class->year] : null,
                'combined_classes' => $p->combinedClasses->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'department' => $c->department ? ['name' => $c->department->name] : null,
                    'year' => $c->year,
                ])->values(),
                'subject' => $p->subject ? ['id' => $p->subject->id, 'name' => $p->subject->name, 'code' => $p->subject->code] : null,
                'hod_approver' => $p->hodApprover ? ['id' => $p->hodApprover->id, 'name' => $p->hodApprover->name] : null,
                'principal_approver' => $p->principalApprover ? ['id' => $p->principalApprover->id, 'name' => $p->principalApprover->name] : null,
            ]);

        $classes = SchoolClass::with('department')->orderBy('name')->get(['id', 'name', 'department_id', 'year']);
        $subjects = Subject::with('department')->orderBy('name')->get(['id', 'name', 'code', 'department_id']);
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

        $data = $this->validatedPlanData();
        $data['employee_id'] = $user->id;
        $data['status'] = ($user->isHOD() || $user->isPrincipal()) ? 'pending_principal' : 'pending_hod';

        $plan = LessonPlan::create($data);
        $this->applyCombinedClasses($plan, $data);
        audit_log('lesson_plan_create', "Created lesson plan: {$data['topic']}");

        return redirect()->back()->with('success', 'Lesson plan created successfully');
    }

    public function approveHod(LessonPlan $lessonPlan)
    {
        $user = Auth::user();

        if (!$user->isHOD()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        if ($lessonPlan->status !== 'pending_hod') {
            return redirect()->back()->with('error', 'Lesson plan is already ' . $lessonPlan->status);
        }

        if ($lessonPlan->employee_id === $user->id) {
            return redirect()->back()->with('error', 'You cannot approve your own lesson plan');
        }

        if ($lessonPlan->employee->department_id !== $user->department_id) {
            return redirect()->back()->with('error', 'You can only approve plans from your department');
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

    public function update(LessonPlan $lessonPlan)
    {
        $user = Auth::user();

        $isOwner = $lessonPlan->employee_id === $user->id;
        $isManagement = $user->isAdmin() || $user->isPrincipal() || $user->isVicePrincipal();
        $isDeptHod = $user->isHOD() && $lessonPlan->employee->department_id === $user->department_id;

        if (!$isOwner && !$isManagement && !$isDeptHod) {
            return redirect()->back()->with('error', 'You can only edit your own or your department\'s lesson plans');
        }

        if (in_array($lessonPlan->status, ['approved', 'rejected'])) {
            return redirect()->back()->with('error', 'Cannot edit a lesson plan that is already ' . $lessonPlan->status);
        }

        if ($user->isStaff() && ($lessonPlan->status ?: 'pending_hod') !== 'pending_hod') {
            return redirect()->back()->with('error', 'Lesson plan can only be edited while pending HOD approval');
        }

        $data = $this->validatedPlanData();

        $lessonPlan->update($data);
        $this->applyCombinedClasses($lessonPlan, $data);
        audit_log('lesson_plan_update', "Updated lesson plan #{$lessonPlan->id}: {$data['topic']}");

        return redirect()->back()->with('success', 'Lesson plan updated successfully');
    }

    public function reject(LessonPlan $lessonPlan)
    {
        $user = Auth::user();

        if ($user->isHOD()) {
            if ($lessonPlan->employee_id === $user->id) {
                return redirect()->back()->with('error', 'You cannot reject your own lesson plan');
            }
            if ($lessonPlan->employee->department_id !== $user->department_id) {
                return redirect()->back()->with('error', 'You can only reject plans from your department');
            }
        } elseif (!$user->isPrincipal() && !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        if (in_array($lessonPlan->status, ['rejected', 'approved'])) {
            return redirect()->back()->with('error', 'Lesson plan cannot be rejected at this stage');
        }

        $lessonPlan->update(['status' => 'rejected']);
        audit_log('lesson_plan_reject', "Rejected lesson plan #{$lessonPlan->id}");

        return redirect()->back()->with('success', 'Lesson plan rejected');
    }

    public function destroy(LessonPlan $lessonPlan)
    {
        $user = Auth::user();

        if ($lessonPlan->employee_id !== $user->id && !$user->isAdmin() && !$user->isPrincipal() && !$user->isHOD()) {
            return redirect()->back()->with('error', 'You are not authorized to delete this lesson plan');
        }

        if ($user->isHOD() && $lessonPlan->employee_id !== $user->id && $lessonPlan->employee->department_id !== $user->department_id) {
            return redirect()->back()->with('error', 'You can only delete plans from your department');
        }

        $status = $lessonPlan->status ?: 'pending_hod';

        if (in_array($status, ['approved', 'rejected']) && !$user->isAdmin() && !$user->isPrincipal()) {
            return redirect()->back()->with('error', 'Cannot delete a lesson plan that is already ' . $status);
        }

        if ($user->isStaff() && $status !== 'pending_hod') {
            return redirect()->back()->with('error', 'Lesson plan can only be deleted while pending HOD approval');
        }

        $topic = $lessonPlan->topic;
        $lessonPlan->delete();
        audit_log('lesson_plan_delete', "Deleted lesson plan #{$lessonPlan->id}: {$topic}");

        return redirect()->back()->with('success', 'Lesson plan deleted successfully');
    }

    private function eligibleBulkQuery(array $ids)
    {
        $user = Auth::user();
        $query = LessonPlan::whereIn('id', $ids);

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

        $plans = $this->eligibleBulkQuery($ids)
            ->where('status', 'pending_hod')
            ->get();

        if ($plans->isEmpty()) {
            return redirect()->back()->with('error', 'No pending plans found to forward');
        }

        $now = now();
        foreach ($plans as $plan) {
            $plan->update([
                'status' => 'pending_principal',
                'hod_approved_by' => $user->id,
                'hod_approved_at' => $now,
            ]);
        }

        audit_log('lesson_plan_bulk_hod_approve', "Bulk forwarded {$plans->count()} lesson plans to principal");

        return redirect()->back()->with('success', "Forwarded {$plans->count()} lesson plan(s) to Principal");
    }

    public function bulkApprovePrincipal()
    {
        $user = Auth::user();
        if (!$user->isPrincipal() && !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $ids = request()->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];

        $plans = $this->eligibleBulkQuery($ids)
            ->whereIn('status', ['pending_hod', 'pending_principal'])
            ->get();

        if ($plans->isEmpty()) {
            return redirect()->back()->with('error', 'No pending plans found to approve');
        }

        foreach ($plans as $plan) {
            $plan->update([
                'status' => 'approved',
                'principal_approved_by' => $user->id,
                'principal_approved_at' => now(),
            ]);
        }

        audit_log('lesson_plan_bulk_principal_approve', "Bulk approved {$plans->count()} lesson plans");

        return redirect()->back()->with('success', "Approved {$plans->count()} lesson plan(s)");
    }

    public function bulkReject()
    {
        $user = Auth::user();
        if (!$user->isHOD() && !$user->isPrincipal() && !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $ids = request()->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];

        $plans = $this->eligibleBulkQuery($ids)
            ->whereNotIn('status', ['approved', 'rejected'])
            ->get();

        if ($plans->isEmpty()) {
            return redirect()->back()->with('error', 'No pending plans found to reject');
        }

        foreach ($plans as $plan) {
            $plan->update(['status' => 'rejected']);
        }

        audit_log('lesson_plan_bulk_reject', "Bulk rejected {$plans->count()} lesson plans");

        return redirect()->back()->with('success', "Rejected {$plans->count()} lesson plan(s)");
    }

    private function validatedPlanData(): array
    {
        $data = request()->validate([
            'class_id' => 'required|integer|exists:classes,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'day' => 'nullable|integer|between:1,6',
            'period' => 'nullable|integer|min:1|max:6',
            'period_to' => 'nullable|integer|min:1|max:6',
            'semester' => 'nullable|string|max:10',
            'topic' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'nullable|string|max:255',
            'plan_date' => 'required|date',
            'combined_classes' => 'nullable|array|max:10',
            'combined_classes.*' => 'required|integer|exists:classes,id',
        ]);

        if (!empty($data['period_to'])) {
            if (empty($data['period'])) {
                throw ValidationException::withMessages(['period' => 'Start period is required when combining periods']);
            }
            if ((int) $data['period_to'] <= (int) $data['period']) {
                throw ValidationException::withMessages(['period_to' => 'End period must be after start period']);
            }
        }

        return $data;
    }

    private function applyCombinedClasses(LessonPlan $lessonPlan, array $data): void
    {
        $combinedIds = array_map('intval', $data['combined_classes'] ?? []);
        $classIds = array_merge([$data['class_id']], $combinedIds);

        if (count($classIds) !== count(array_unique($classIds))) {
            throw ValidationException::withMessages(['combined_classes' => 'A class cannot be combined with itself']);
        }

        $lessonPlan->combinedClasses()->sync($combinedIds);
    }
}
