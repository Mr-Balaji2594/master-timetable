<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BulkUploadController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\CommonPaperController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LessonPlanController;
use App\Http\Controllers\LessonReportController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\StaffSubjectController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SubstitutionController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\WorkloadController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->middleware('role:any');

    Route::resource('departments', DepartmentController::class)->except(['show', 'edit', 'create'])->middleware('role:management');
    Route::resource('employees', EmployeeController::class)->except(['show', 'edit', 'create'])->middleware('role:management');
    Route::post('employees/{employee}/reset-password', [EmployeeController::class, 'resetPassword'])->name('employees.reset-password')->middleware('role:management');

    Route::resource('classes', ClassController::class)->except(['show', 'edit', 'create'])->middleware('role:management');
    Route::resource('subjects', SubjectController::class)->except(['show', 'edit', 'create'])->middleware('role:management');

    Route::resource('staff-subjects', StaffSubjectController::class)->except(['show', 'edit', 'create'])->middleware('role:admin,hod');

    Route::get('timetable', [TimetableController::class, 'index'])->name('timetable.index')->middleware('role:any');
    Route::post('timetable', [TimetableController::class, 'store'])->name('timetable.store')->middleware('role:any');
    Route::delete('timetable/{slot}', [TimetableController::class, 'destroy'])->name('timetable.destroy')->middleware('role:any');

    Route::resource('leave', LeaveController::class)->except(['show', 'edit', 'create'])->middleware('role:any');
    Route::post('leave/{leave}/approve-hod', [LeaveController::class, 'approveHod'])->name('leave.approve-hod')->middleware('role:hod,principal,admin');
    Route::post('leave/{leave}/approve-principal', [LeaveController::class, 'approvePrincipal'])->name('leave.approve-principal')->middleware('role:principal,admin');
    Route::post('leave/{leave}/reject', [LeaveController::class, 'reject'])->name('leave.reject')->middleware('role:hod,principal,admin');

    Route::resource('substitution', SubstitutionController::class)->except(['show', 'edit', 'create'])->middleware('role:any');

    Route::get('workload', [WorkloadController::class, 'index'])->name('workload.index')->middleware('role:any');
    Route::post('workload/calculate', [WorkloadController::class, 'calculate'])->name('workload.calculate')->middleware('role:admin,principal');

    Route::resource('lesson-plans', LessonPlanController::class)->except(['show', 'edit', 'create'])->middleware('role:any');
    Route::post('lesson-plans/{lessonPlan}/approve-hod', [LessonPlanController::class, 'approveHod'])->name('lesson-plans.approve-hod')->middleware('role:hod,principal,admin');
    Route::post('lesson-plans/{lessonPlan}/approve-principal', [LessonPlanController::class, 'approvePrincipal'])->name('lesson-plans.approve-principal')->middleware('role:principal,admin');
    Route::post('lesson-plans/{lessonPlan}/reject', [LessonPlanController::class, 'reject'])->name('lesson-plans.reject')->middleware('role:hod,principal,admin');

    Route::get('lesson-reports', [LessonReportController::class, 'index'])->name('lesson-reports.index')->middleware('role:any');
    Route::get('lesson-reports/export', [LessonReportController::class, 'export'])->name('lesson-reports.export')->middleware('role:admin,principal');

    Route::get('common-papers', [CommonPaperController::class, 'index'])->name('common-papers.index')->middleware('role:admin,principal');
    Route::post('common-papers/allocate', [CommonPaperController::class, 'allocate'])->name('common-papers.allocate')->middleware('role:admin,principal');
    Route::put('common-papers/allocate/{commonPaperAllocation}', [CommonPaperController::class, 'update'])->name('common-papers.update')->middleware('role:admin,principal');
    Route::delete('common-papers/allocate/{commonPaperAllocation}', [CommonPaperController::class, 'destroy'])->name('common-papers.destroy')->middleware('role:admin,principal');

    Route::get('bulk-upload', [BulkUploadController::class, 'index'])->name('bulk-upload.index')->middleware('role:admin');
    Route::post('bulk-upload/import', [BulkUploadController::class, 'import'])->name('bulk-upload.import')->middleware('role:admin');

    Route::get('leave-balance', [LeaveBalanceController::class, 'index'])->name('leave-balance.index')->middleware('role:admin');
    Route::put('leave-balance/{employee}', [LeaveBalanceController::class, 'update'])->name('leave-balance.update')->middleware('role:admin');
    Route::post('leave-balance/reset', [LeaveBalanceController::class, 'reset'])->name('leave-balance.reset')->middleware('role:admin');

    Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log.index')->middleware('role:admin');

    Route::get('change-password', [PasswordController::class, 'index'])->name('change-password.index')->middleware('role:any');
    Route::post('change-password', [PasswordController::class, 'update'])->name('change-password.update')->middleware('role:any');
});

Route::get('/up', function () {
    return response()->json(['status' => 'ok']);
});
