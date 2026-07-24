<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LessonPlan;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TimetableSlot;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $stats = [];
        $pendingLeaves = [];
        $recentPlans = [];
        $todaySlots = [];

        if ($user->isAdmin() || $user->isPrincipal() || $user->isVicePrincipal()) {
            $stats = [
                'departments_count' => Department::count(),
                'employees_count' => Employee::where('is_active', true)->count(),
                'classes_count' => SchoolClass::count(),
                'subjects_count' => Subject::count(),
            ];
            $pendingLeaves = LeaveRequest::with('employee')
                ->whereIn('status', ['pending_hod', 'pending_principal'])
                ->orderByDesc('leave_date')
                ->take(5)
                ->get()
                ->map(fn($l) => [
                    'id' => $l->id,
                    'employee' => $l->employee ? ['name' => $l->employee->name] : null,
                    'nature' => $l->nature,
                    'leave_date' => $l->leave_date,
                    'status' => $l->status,
                ]);
        } elseif ($user->isHOD()) {
            $deptIds = Employee::where('department_id', $user->department_id)->pluck('id');
            $stats = [
                'departments_count' => 1,
                'employees_count' => Employee::where('department_id', $user->department_id)->where('is_active', true)->count(),
                'classes_count' => SchoolClass::where('department_id', $user->department_id)->count(),
                'subjects_count' => Subject::where('department_id', $user->department_id)->count(),
            ];
            $pendingLeaves = LeaveRequest::with('employee')
                ->whereIn('employee_id', $deptIds)
                ->where('status', 'pending_hod')
                ->orderByDesc('leave_date')
                ->take(5)
                ->get()
                ->map(fn($l) => [
                    'id' => $l->id,
                    'employee' => $l->employee ? ['name' => $l->employee->name] : null,
                    'nature' => $l->nature,
                    'leave_date' => $l->leave_date,
                    'status' => $l->status,
                ]);
        } else {
            $today = now()->dayOfWeek;
            $today = $today === 0 ? 7 : $today;

            $stats = [
                'my_leave_count' => LeaveRequest::where('employee_id', $user->id)->count(),
                'my_pending_leave' => LeaveRequest::where('employee_id', $user->id)->where('status', 'pending_hod')->count(),
                'my_plans_count' => LessonPlan::where('employee_id', $user->id)->count(),
                'my_classes_today' => TimetableSlot::where('employee_id', $user->id)->where('day_of_week', $today)->count(),
            ];

            $recentPlans = LessonPlan::with(['class', 'subject'])
                ->where('employee_id', $user->id)
                ->orderByDesc('plan_date')
                ->take(5)
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'topic' => $p->topic,
                    'class' => $p->class ? ['name' => $p->class->name] : null,
                    'subject' => $p->subject ? ['name' => $p->subject->name] : null,
                    'plan_date' => $p->plan_date,
                    'status' => $p->status,
                ]);

            $dayOfWeek = now()->dayOfWeek;
            $todaySlots = TimetableSlot::with(['class', 'subject'])
                ->where('employee_id', $user->id)
                ->where('day_of_week', $dayOfWeek === 0 ? 7 : $dayOfWeek)
                ->orderBy('period_no')
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'period_no' => $s->period_no,
                    'class' => $s->class ? ['name' => $s->class->name] : null,
                    'subject' => $s->subject ? ['name' => $s->subject->name, 'code' => $s->subject->code] : null,
                    'room_no' => $s->room_no,
                ]);
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'pendingLeaves' => $pendingLeaves,
            'recentPlans' => $recentPlans,
            'todaySlots' => $todaySlots,
        ]);
    }
}
