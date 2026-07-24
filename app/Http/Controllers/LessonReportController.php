<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LessonPlan;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LessonReportController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $reports = LessonPlan::with(['employee', 'class', 'subject'])
            ->when(!$user->isAdmin() && !$user->isPrincipal() && !$user->isVicePrincipal(), fn($q) => $q->whereHas('employee', fn($q) => $q->where('department_id', $user->department_id)))
            ->when(request('employee_id'), fn($q, $v) => $q->where('employee_id', $v))
            ->when(request('class_id'), fn($q, $v) => $q->where('class_id', $v))
            ->when(request('subject_id'), fn($q, $v) => $q->where('subject_id', $v))
            ->when(request('status'), fn($q, $v) => $q->where('status', $v))
            ->when(request('from_date'), fn($q, $v) => $q->whereDate('plan_date', '>=', $v))
            ->when(request('to_date'), fn($q, $v) => $q->whereDate('plan_date', '<=', $v))
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
                'hod_approved_at' => $p->hod_approved_at?->format('d/m/Y H:i:s'),
                'principal_approved_at' => $p->principal_approved_at?->format('d/m/Y H:i:s'),
                'employee' => $p->employee ? ['id' => $p->employee->id, 'emp_id' => $p->employee->emp_id, 'name' => $p->employee->name] : null,
                'class' => $p->class ? ['id' => $p->class->id, 'name' => $p->class->name] : null,
                'subject' => $p->subject ? ['id' => $p->subject->id, 'name' => $p->subject->name, 'code' => $p->subject->code] : null,
            ]);

        $isScoped = !$user->isAdmin() && !$user->isPrincipal() && !$user->isVicePrincipal();
        $deptScope = fn($q) => $q->where('department_id', $user->department_id);

        $employees = Employee::where('is_active', true)
            ->when($isScoped, $deptScope)
            ->orderBy('name')
            ->get(['id', 'emp_id', 'name']);

        $classes = SchoolClass::when($isScoped, $deptScope)
            ->orderBy('name')
            ->get(['id', 'name']);

        $subjects = Subject::when($isScoped, $deptScope)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('LessonReport/Index', [
            'reports' => $reports,
            'employees' => $employees,
            'classes' => $classes,
            'subjects' => $subjects,
            'filters' => request()->only(['employee_id', 'class_id', 'subject_id', 'status', 'from_date', 'to_date']),
        ]);
    }

    public function export()
    {
        $user = Auth::user();

        $plans = LessonPlan::with(['employee', 'class', 'subject'])
            ->when(!$user->isAdmin() && !$user->isPrincipal() && !$user->isVicePrincipal(), fn($q) => $q->whereHas('employee', fn($q) => $q->where('department_id', $user->department_id)))
            ->when(request('from_date'), fn($q, $v) => $q->whereDate('plan_date', '>=', $v))
            ->when(request('to_date'), fn($q, $v) => $q->whereDate('plan_date', '<=', $v))
            ->when(request('status'), fn($q, $v) => $q->where('status', $v))
            ->orderByDesc('plan_date')
            ->get();

        $filename = 'lesson_reports_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($plans) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee', 'Emp ID', 'Class', 'Subject', 'Topic', 'Unit', 'Plan Date', 'Status', 'HOD Approved At', 'Principal Approved At']);

            foreach ($plans as $p) {
                fputcsv($handle, [
                    $p->employee?->name ?? '',
                    $p->employee?->emp_id ?? '',
                    $p->class?->name ?? '',
                    $p->subject?->name ?? '',
                    $p->topic,
                    $p->unit,
                    $p->plan_date?->toDateString() ?? '',
                    $p->status,
                    $p->hod_approved_at?->toDateTimeString() ?? '',
                    $p->principal_approved_at?->toDateTimeString() ?? '',
                ]);
            }

            fclose($handle);
        };

        audit_log('lesson_report_export', "Exported lesson reports to CSV: {$filename}");

        return response()->stream($callback, 200, $headers);
    }
}
