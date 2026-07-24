<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\TimetableSlot;
use App\Models\Workload;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorkloadController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $workloads = Workload::with('employee')
            ->when(!$user->isAdmin() && !$user->isPrincipal() && !$user->isVicePrincipal(), fn($q) => $q->whereHas('employee', fn($q) => $q->where('department_id', $user->department_id)))
            ->orderByDesc('computed_date')
            ->get()
            ->map(fn($w) => [
                'id' => $w->id,
                'employee_id' => $w->employee_id,
                'total_hours' => $w->total_hours,
                'period_week' => $w->period_week,
                'computed_date' => $w->computed_date,
                'employee' => $w->employee ? ['id' => $w->employee->id, 'emp_id' => $w->employee->emp_id, 'name' => $w->employee->name] : null,
            ]);

        return Inertia::render('Workload/Index', ['workloads' => $workloads]);
    }

    public function calculate()
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isPrincipal()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $employees = Employee::where('is_active', true)
            ->when(!$user->isAdmin() && !$user->isPrincipal(), fn($q) => $q->where('department_id', $user->department_id))
            ->get();

        $periodWeek = now()->format('Y-W');
        $computedDate = now()->toDateString();

        foreach ($employees as $employee) {
            $totalHours = TimetableSlot::where('employee_id', $employee->id)
                ->join('subjects', 'timetable.subject_id', '=', 'subjects.id')
                ->sum('subjects.lecture_hours_per_week');

            Workload::updateOrCreate(
                ['employee_id' => $employee->id, 'period_week' => $periodWeek],
                ['total_hours' => $totalHours, 'computed_date' => $computedDate]
            );
        }

        audit_log('workload_calculate', "Calculated workload for period {$periodWeek}");

        return redirect()->back()->with('success', 'Workload calculated successfully');
    }
}
