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

        $slots = TimetableSlot::with(['class.department', 'subject', 'employee.department', 'hodApprover', 'principalApprover'])
            ->when($user->isStaff(), fn($q) => $q->where('employee_id', $user->id))
            ->when($isHodScoped && !$user->isStaff(), fn($q) => $q->whereHas('employee', $deptScope))
            ->when(request('employee_id'), fn($q, $v) => $q->where('employee_id', $v))
            ->when(request('class_id'), fn($q, $v) => $q->where('class_id', $v))
            ->when(request('day_of_week'), fn($q, $v) => $q->where('day_of_week', $v))
            ->orderBy('day_of_week')
            ->orderBy('period_no')
            ->orderBy('class_id')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'class_id' => $s->class_id,
                'subject_id' => $s->subject_id,
                'employee_id' => $s->employee_id,
                'day_of_week' => $s->day_of_week,
                'period_no' => $s->period_no,
                'semester' => $s->semester,
                'room_no' => $s->room_no,
                'combined_group_id' => $s->combined_group_id,
                'status' => $s->status,
                'hod_approved_by' => $s->hod_approved_by,
                'hod_approved_at' => $s->hod_approved_at?->format('d/m/Y H:i:s'),
                'principal_approved_by' => $s->principal_approved_by,
                'principal_approved_at' => $s->principal_approved_at?->format('d/m/Y H:i:s'),
                'class' => $s->class ? ['id' => $s->class->id, 'name' => $s->class->name, 'year' => $s->class->year, 'dept_code' => $s->class->department?->code, 'department' => $s->class->department?->name] : null,
                'subject' => $s->subject ? ['id' => $s->subject->id, 'name' => $s->subject->name, 'code' => $s->subject->code] : null,
                'employee' => $s->employee ? ['id' => $s->employee->id, 'emp_id' => $s->employee->emp_id, 'name' => $s->employee->name] : null,
                'hod_approver' => $s->hodApprover ? ['id' => $s->hodApprover->id, 'name' => $s->hodApprover->name] : null,
                'principal_approver' => $s->principalApprover ? ['id' => $s->principalApprover->id, 'name' => $s->principalApprover->name] : null,
            ]);

        $classes = SchoolClass::with('department')
            ->when($isHodScoped, $deptScope)
            ->orderBy('name')
            ->get(['id', 'name', 'department_id', 'year'])
            ->map(fn($c) => [
                'id' => $c->id,
                'label' => "{$c->name} - {$c->department?->name} - {$c->year}",
            ]);

        $allClasses = SchoolClass::with('department')
            ->orderBy('name')
            ->get(['id', 'name', 'department_id', 'year'])
            ->map(fn($c) => [
                'id' => $c->id,
                'label' => "{$c->name} - {$c->department?->name} - {$c->year}",
                'department' => $c->department?->name,
                'dept_code' => $c->department?->code,
            ]);

        $employees = Employee::with('department:id,name,code')
            ->where('is_active', true)
            ->when($isHodScoped, fn($q) => $q->where('department_id', $user->department_id))
            ->orderBy('name')
            ->get(['id', 'emp_id', 'name', 'designation', 'department_id']);

        $subjects = Subject::with('department:id,name,code')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'department_id', 'is_common']);

        return Inertia::render('Timetable/Index', [
            'slots' => $slots,
            'employees' => $employees,
            'classes' => $classes,
            'allClasses' => $allClasses,
            'subjects' => $subjects,
            'filters' => request()->only(['employee_id', 'class_id', 'day_of_week']),
        ]);
    }

    public function store()
    {
        $user = Auth::user();

        $data = $this->validateRequest();

        if ($user->isStaff()) {
            $data['employee_id'] = $user->id;
            $data['status'] = 'pending_hod';
        } else {
            $data['status'] = 'approved';
        }

        return $this->saveSlots($data, $user, 'created successfully');
    }

    public function update(TimetableSlot $slot)
    {
        $user = Auth::user();

        $this->authorizeEdit($slot, $user);

        $data = $this->validateRequest();

        if ($user->isStaff()) {
            $data['employee_id'] = $user->id;
            $data['status'] = $slot->status === 'rejected' ? 'pending_hod' : $slot->status;
        } else {
            $data['status'] = $slot->status ?: 'approved';
        }

        $groupId = $slot->combined_group_id ?? $slot->id;
        TimetableSlot::where('combined_group_id', $groupId)->orWhere('id', $groupId)->delete();

        return $this->saveSlots($data, $user, 'updated successfully');
    }

    public function destroy(TimetableSlot $slot)
    {
        $user = Auth::user();

        $this->authorizeDelete($slot, $user);

        $groupId = $slot->combined_group_id ?? $slot->id;
        $count = TimetableSlot::where('combined_group_id', $groupId)->orWhere('id', $groupId)->count();
        TimetableSlot::where('combined_group_id', $groupId)->orWhere('id', $groupId)->delete();

        audit_log('timetable_delete', "Deleted timetable slot #{$slot->id}" . ($count > 1 ? " (combined group of {$count})" : ''));

        return redirect()->back()->with('success', 'Timetable slot deleted successfully');
    }

    public function approveHod(TimetableSlot $slot)
    {
        $user = Auth::user();
        if (!$user->isHOD() && !$user->isAdmin() && !$user->isPrincipal()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        if ($slot->status !== 'pending_hod') {
            return redirect()->back()->with('error', 'Slot is already ' . $slot->status);
        }

        if ($slot->employee_id === $user->id) {
            return redirect()->back()->with('error', 'You cannot approve your own timetable slot');
        }

        if ($user->isHOD() && $slot->employee->department_id !== $user->department_id) {
            return redirect()->back()->with('error', 'You can only approve slots from your department');
        }

        $this->updateGroupStatus($slot, 'pending_principal', [
            'hod_approved_by' => $user->id,
            'hod_approved_at' => now(),
        ]);

        audit_log('timetable_hod_approve', "HOD forwarded timetable slot #{$slot->id} to principal");

        return redirect()->back()->with('success', 'Timetable slot forwarded to Principal');
    }

    public function approvePrincipal(TimetableSlot $slot)
    {
        $user = Auth::user();
        if (!$user->isPrincipal() && !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        if (!in_array($slot->status, ['pending_hod', 'pending_principal'])) {
            return redirect()->back()->with('error', 'Slot cannot be approved at this stage');
        }

        $this->updateGroupStatus($slot, 'approved', [
            'principal_approved_by' => $user->id,
            'principal_approved_at' => now(),
        ]);

        audit_log('timetable_principal_approve', "Principal approved timetable slot #{$slot->id}");

        return redirect()->back()->with('success', 'Timetable slot approved by Principal');
    }

    public function reject(TimetableSlot $slot)
    {
        $user = Auth::user();

        if ($user->isHOD()) {
            if ($slot->employee_id === $user->id) {
                return redirect()->back()->with('error', 'You cannot reject your own timetable slot');
            }
            if ($slot->employee->department_id !== $user->department_id) {
                return redirect()->back()->with('error', 'You can only reject slots from your department');
            }
        } elseif (!$user->isPrincipal() && !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        if (in_array($slot->status, ['approved', 'rejected'])) {
            return redirect()->back()->with('error', 'Slot cannot be rejected at this stage');
        }

        $this->updateGroupStatus($slot, 'rejected');

        audit_log('timetable_reject', "Rejected timetable slot #{$slot->id}");

        return redirect()->back()->with('success', 'Timetable slot rejected');
    }

    private function authorizeEdit(TimetableSlot $slot, $user): void
    {
        if ($user->isStaff()) {
            if ($slot->employee_id !== $user->id) {
                abort(403, 'You can only edit your own timetable slots');
            }
            if ($slot->status === 'approved') {
                abort(403, 'Approved slots cannot be edited');
            }
            return;
        }

        if (!$user->isAdmin() && !$user->isHOD() && !$user->isPrincipal() && !$user->isVicePrincipal()) {
            abort(403, 'Unauthorized');
        }
    }

    private function authorizeDelete(TimetableSlot $slot, $user): void
    {
        if ($user->isStaff()) {
            if ($slot->employee_id !== $user->id) {
                abort(403, 'You can only delete your own timetable slots');
            }
            if ($slot->status === 'approved') {
                abort(403, 'Approved slots cannot be deleted');
            }
            return;
        }

        if (!$user->isAdmin() && !$user->isHOD() && !$user->isPrincipal() && !$user->isVicePrincipal()) {
            abort(403, 'Unauthorized');
        }
    }

    private function updateGroupStatus(TimetableSlot $slot, string $status, array $extra = []): void
    {
        $groupId = $slot->combined_group_id ?? $slot->id;
        TimetableSlot::where('combined_group_id', $groupId)->orWhere('id', $groupId)
            ->update(array_merge(['status' => $status], $extra));
    }

    private function validateRequest(): array
    {
        return request()->validate([
            'class_id' => 'required|integer|exists:classes,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'employee_id' => 'required|integer|exists:employees,id',
            'day_of_week' => 'required|integer|between:1,6',
            'period_no' => 'required|integer|min:1|max:5',
            'semester' => 'nullable|string|max:10',
            'room_no' => 'nullable|string|max:20',
            'combined_classes' => 'nullable|array|max:10',
            'combined_classes.*.class_id' => 'required|integer|exists:classes,id',
            'combined_classes.*.subject_id' => 'required|integer|exists:subjects,id',
        ]);
    }

    private function saveSlots(array $data, $user, string $verb)
    {
        $classIds = array_merge([$data['class_id']], array_column($data['combined_classes'] ?? [], 'class_id'));

        if (count($classIds) !== count(array_unique($classIds))) {
            return redirect()->back()->withErrors([
                'combined_classes' => 'A class cannot be combined with itself',
            ])->withInput();
        }

        $conflict = $this->findConflicts($classIds, $data['employee_id'], $data['day_of_week'], $data['period_no']);
        if ($conflict) {
            return redirect()->back()->withErrors($conflict)->withInput();
        }

        $semester = $data['semester'] ?? null;
        $roomNo = $data['room_no'] ?? null;
        $status = $data['status'] ?? 'approved';

        $primary = TimetableSlot::create([
            'class_id' => $data['class_id'],
            'subject_id' => $data['subject_id'],
            'employee_id' => $data['employee_id'],
            'day_of_week' => $data['day_of_week'],
            'period_no' => $data['period_no'],
            'semester' => $semester,
            'room_no' => $roomNo,
            'combined_group_id' => null,
            'status' => $status,
        ]);

        $combinedCount = 0;
        foreach (($data['combined_classes'] ?? []) as $combined) {
            TimetableSlot::create([
                'class_id' => $combined['class_id'],
                'subject_id' => $combined['subject_id'],
                'employee_id' => $data['employee_id'],
                'day_of_week' => $data['day_of_week'],
                'period_no' => $data['period_no'],
                'semester' => $semester,
                'room_no' => $roomNo,
                'combined_group_id' => $primary->id,
                'status' => $status,
            ]);
            $combinedCount++;
        }

        $detail = "Class #{$data['class_id']}, Day {$data['day_of_week']}, Period {$data['period_no']}";
        if ($combinedCount > 0) {
            $detail .= " + {$combinedCount} combined class(es)";
        }
        audit_log('timetable_create', "{$verb} timetable slot: {$detail}");

        $message = $combinedCount > 0
            ? "Timetable slot (combined with {$combinedCount} class(es)) {$verb}"
            : "Timetable slot {$verb}";

        return redirect()->back()->with('success', $message);
    }

    private function findConflicts(array $classIds, int $employeeId, int $day, int $period, ?int $excludeSlotId = null): ?array
    {
        $query = TimetableSlot::where('day_of_week', $day)->where('period_no', $period);

        if ($excludeSlotId) {
            $query->where(function ($q) use ($excludeSlotId) {
                $q->where('id', '!=', $excludeSlotId)
                    ->where(function ($inner) use ($excludeSlotId) {
                        $inner->whereNull('combined_group_id')
                            ->orWhere('combined_group_id', '!=', $excludeSlotId);
                    });
            });
        }

        $existing = $query->get(['id', 'class_id', 'employee_id', 'combined_group_id']);

        if ($existing->where('employee_id', $employeeId)->isNotEmpty()) {
            return ['employee_id' => 'This employee is already scheduled for this day and period'];
        }

        $busyClassIds = $existing->pluck('class_id')->intersect($classIds);
        if ($busyClassIds->isNotEmpty()) {
            return ['class_id' => 'One of the selected classes is already scheduled for this day and period'];
        }

        return null;
    }
}