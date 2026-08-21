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

        $reports = LessonPlan::with(['employee.department', 'class.department', 'subject', 'combinedClasses.department', 'hodApprover', 'principalApprover'])
            ->when($user->isHOD(), fn($q) => $q->whereHas('employee', fn($q) => $q->where('department_id', $user->department_id)))
            ->when($user->isStaff(), fn($q) => $q->where('employee_id', $user->id))
            ->where('status', 'approved')
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
                'plan_date' => $p->plan_date?->format('Y-m-d'),
                'status' => $p->status,
                'hod_approved_at' => $p->hod_approved_at?->format('d/m/Y H:i:s'),
                'principal_approved_at' => $p->principal_approved_at?->format('d/m/Y H:i:s'),
                'employee' => $p->employee ? ['id' => $p->employee->id, 'emp_id' => $p->employee->emp_id, 'name' => $p->employee->name, 'designation' => $p->employee->designation, 'department' => $p->employee->department ? ['name' => $p->employee->department->name] : null] : null,
                'class' => $p->class ? ['id' => $p->class->id, 'name' => $p->class->name, 'department' => $p->class->department ? ['name' => $p->class->department->name] : null, 'year' => $p->class->year] : null,
                'subject' => $p->subject ? ['id' => $p->subject->id, 'name' => $p->subject->name, 'code' => $p->subject->code] : null,
                'combined_classes' => $p->combinedClasses->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'department' => $c->department ? ['name' => $c->department->name] : null,
                    'year' => $c->year,
                ])->values(),
                'hod_approver' => $p->hodApprover ? ['name' => $p->hodApprover->name] : null,
                'principal_approver' => $p->principalApprover ? ['name' => $p->principalApprover->name] : null,
            ]);

        $isScoped = ($user->isHOD() || $user->isStaff());
        $deptScope = fn($q) => $q->where('department_id', $user->department_id);

        $employees = Employee::where('is_active', true)
            ->when($isScoped, $deptScope)
            ->when($user->isStaff(), fn($q) => $q->where('id', $user->id))
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

        $plans = LessonPlan::with(['employee.department', 'class.department', 'subject', 'combinedClasses.department', 'hodApprover', 'principalApprover'])
            ->when($user->isHOD(), fn($q) => $q->whereHas('employee', fn($q) => $q->where('department_id', $user->department_id)))
            ->when($user->isStaff(), fn($q) => $q->where('employee_id', $user->id))
            ->where('status', 'approved')
            ->when(request('employee_id'), fn($q, $v) => $q->where('employee_id', $v))
            ->when(request('class_id'), fn($q, $v) => $q->where('class_id', $v))
            ->when(request('subject_id'), fn($q, $v) => $q->where('subject_id', $v))
            ->when(request('status'), fn($q, $v) => $q->where('status', $v))
            ->when(request('from_date'), fn($q, $v) => $q->whereDate('plan_date', '>=', $v))
            ->when(request('to_date'), fn($q, $v) => $q->whereDate('plan_date', '<=', $v))
            ->get()
            ->sortBy(fn($p) => [
                strtolower($p->employee?->name ?? ''),
                strtolower($p->class?->name ?? ''),
                $p->plan_date ?? '',
            ])
            ->values();

        $filename = 'lesson_reports_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $dayLabels = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI'];

        $callback = function () use ($plans, $dayLabels) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Employee Name', 'Emp ID', 'Designation', 'Employee Department',
                'Class', 'Class Department', 'Combined Classes',
                'Subject', 'Subject Code', 'Day', 'Period', 'Semester',
                'Topic', 'Unit', 'Description', 'Plan Date',
                'Status', 'HOD Approved By', 'HOD Approved At', 'Principal Approved By', 'Principal Approved At',
            ]);

            foreach ($plans as $p) {
                $combined = $p->combinedClasses->map(fn($c) => $c->name)->implode('; ');
                $day = $p->day ? ($dayLabels[$p->day] ?? $p->day) : '';

                fputcsv($handle, [
                    $p->employee?->name ?? '',
                    $p->employee?->emp_id ?? '',
                    $p->employee?->designation ?? '',
                    $p->employee?->department?->name ?? '',
                    $p->class?->name ?? '',
                    $p->class?->department?->name ?? '',
                    $combined,
                    $p->subject?->name ?? '',
                    $p->subject?->code ?? '',
                    $day,
                    $p->period ?? '',
                    $p->semester ?? '',
                    $p->topic,
                    $p->unit ?? '',
                    $p->description ?? '',
                    $p->plan_date?->toDateString() ?? '',
                    $p->status,
                    $p->hodApprover?->name ?? '',
                    $p->hod_approved_at?->toDateTimeString() ?? '',
                    $p->principalApprover?->name ?? '',
                    $p->principal_approved_at?->toDateTimeString() ?? '',
                ]);
            }

            fclose($handle);
        };

        audit_log('lesson_report_export', "Exported lesson reports to CSV: {$filename}");

        return response()->stream($callback, 200, $headers);
    }
}
