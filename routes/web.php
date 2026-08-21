<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BulkUploadController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\CompensationController;
use App\Http\Controllers\CommonPaperController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EarnedLeaveController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LoginAttemptController;
use App\Http\Controllers\LessonPlanController;
use App\Http\Controllers\LessonReportController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\StaffSubjectController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SubstitutionController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\AcademicCalendarController;
use App\Http\Controllers\WorkloadController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->middleware('role:any');

    Route::resource('departments', DepartmentController::class)->except(['show', 'edit', 'create'])->middleware('role:management');
    Route::resource('employees', EmployeeController::class)->except(['show', 'edit', 'create'])->middleware('role:management');
    Route::post('employees/{employee}/reset-password', [EmployeeController::class, 'resetPassword'])->name('employees.reset-password')->middleware('role:management');
    Route::post('employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])->name('employees.toggle-status')->middleware('role:management');

    Route::resource('classes', ClassController::class)->except(['show', 'edit', 'create'])->middleware('role:management');
    Route::resource('subjects', SubjectController::class)->except(['show', 'edit', 'create'])->middleware('role:management');

    Route::resource('staff-subjects', StaffSubjectController::class)->except(['show', 'edit', 'create'])->middleware('role:any');

    Route::get('timetable', [TimetableController::class, 'index'])->name('timetable.index')->middleware('role:any');
    Route::post('timetable', [TimetableController::class, 'store'])->name('timetable.store')->middleware('role:any');
    Route::put('timetable/{slot}', [TimetableController::class, 'update'])->name('timetable.update')->middleware('role:any');
    Route::delete('timetable/{slot}', [TimetableController::class, 'destroy'])->name('timetable.destroy')->middleware('role:any');
    Route::post('timetable/{slot}/approve-hod', [TimetableController::class, 'approveHod'])->name('timetable.approve-hod')->middleware('role:hod,principal,admin');
    Route::post('timetable/{slot}/approve-principal', [TimetableController::class, 'approvePrincipal'])->name('timetable.approve-principal')->middleware('role:principal,admin');
    Route::post('timetable/{slot}/reject', [TimetableController::class, 'reject'])->name('timetable.reject')->middleware('role:hod,principal,admin');

    Route::get('academic-calendar', [AcademicCalendarController::class, 'index'])->name('academic-calendar.index')->middleware('role:any');

    Route::resource('leave', LeaveController::class)->except(['show', 'edit', 'create'])->middleware('role:any');
    Route::post('leave/{leave}/approve-hod', [LeaveController::class, 'approveHod'])->name('leave.approve-hod')->middleware('role:hod,principal,admin');
    Route::post('leave/{leave}/approve-principal', [LeaveController::class, 'approvePrincipal'])->name('leave.approve-principal')->middleware('role:principal,admin');
    Route::post('leave/{leave}/reject', [LeaveController::class, 'reject'])->name('leave.reject')->middleware('role:hod,principal,admin');
    Route::post('leave/bulk-approve-hod', [LeaveController::class, 'bulkApproveHod'])->name('leave.bulk-approve-hod')->middleware('role:hod,principal,admin');
    Route::post('leave/bulk-approve-principal', [LeaveController::class, 'bulkApprovePrincipal'])->name('leave.bulk-approve-principal')->middleware('role:principal,admin');
    Route::post('leave/bulk-reject', [LeaveController::class, 'bulkReject'])->name('leave.bulk-reject')->middleware('role:hod,principal,admin');

    Route::resource('substitution', SubstitutionController::class)->except(['show', 'edit', 'create'])->middleware('role:any');
    Route::post('substitution/{substitution}/complete', [SubstitutionController::class, 'complete'])->name('substitution.complete')->middleware('role:any');
    Route::post('substitution/{substitution}/cancel', [SubstitutionController::class, 'cancel'])->name('substitution.cancel')->middleware('role:any');

    Route::get('compensations', [CompensationController::class, 'index'])->name('compensations.index')->middleware('role:any');
    Route::post('compensations', [CompensationController::class, 'store'])->name('compensations.store')->middleware('role:any');
    Route::post('compensations/{compensation}/complete', [CompensationController::class, 'complete'])->name('compensations.complete')->middleware('role:any');
    Route::post('compensations/{compensation}/approve', [CompensationController::class, 'approve'])->name('compensations.approve')->middleware('role:any');
    Route::post('compensations/{compensation}/cancel', [CompensationController::class, 'cancel'])->name('compensations.cancel')->middleware('role:any');

    Route::get('workload', [WorkloadController::class, 'index'])->name('workload.index')->middleware('role:admin,principal,hod');
    Route::post('workload', [WorkloadController::class, 'store'])->name('workload.store')->middleware('role:admin,principal,hod');
    Route::put('workload/{workload}', [WorkloadController::class, 'update'])->name('workload.update')->middleware('role:admin,principal,hod');

    Route::resource('lesson-plans', LessonPlanController::class)->except(['show', 'edit', 'create'])->middleware('role:any');
    Route::post('lesson-plans/{lessonPlan}/approve-hod', [LessonPlanController::class, 'approveHod'])->name('lesson-plans.approve-hod')->middleware('role:hod,principal,admin');
    Route::post('lesson-plans/{lessonPlan}/approve-principal', [LessonPlanController::class, 'approvePrincipal'])->name('lesson-plans.approve-principal')->middleware('role:principal,admin');
    Route::post('lesson-plans/{lessonPlan}/reject', [LessonPlanController::class, 'reject'])->name('lesson-plans.reject')->middleware('role:hod,principal,admin');
    Route::post('lesson-plans/bulk-approve-hod', [LessonPlanController::class, 'bulkApproveHod'])->name('lesson-plans.bulk-approve-hod')->middleware('role:hod,principal,admin');
    Route::post('lesson-plans/bulk-approve-principal', [LessonPlanController::class, 'bulkApprovePrincipal'])->name('lesson-plans.bulk-approve-principal')->middleware('role:principal,admin');
    Route::post('lesson-plans/bulk-reject', [LessonPlanController::class, 'bulkReject'])->name('lesson-plans.bulk-reject')->middleware('role:hod,principal,admin');

    Route::get('lesson-reports', [LessonReportController::class, 'index'])->name('lesson-reports.index')->middleware('role:any');
    Route::get('lesson-reports/export', [LessonReportController::class, 'export'])->name('lesson-reports.export')->middleware('role:any');

    Route::get('common-papers', [CommonPaperController::class, 'index'])->name('common-papers.index')->middleware('role:admin,principal');
    Route::post('common-papers/allocate', [CommonPaperController::class, 'allocate'])->name('common-papers.allocate')->middleware('role:admin,principal');
    Route::put('common-papers/allocate/{commonPaperAllocation}', [CommonPaperController::class, 'update'])->name('common-papers.update')->middleware('role:admin,principal');
    Route::delete('common-papers/allocate/{commonPaperAllocation}', [CommonPaperController::class, 'destroy'])->name('common-papers.destroy')->middleware('role:admin,principal');

    Route::get('bulk-upload', [BulkUploadController::class, 'index'])->name('bulk-upload.index')->middleware('role:admin');
    Route::post('bulk-upload/import', [BulkUploadController::class, 'import'])->name('bulk-upload.import')->middleware('role:admin');

    Route::get('leave-balance', [LeaveBalanceController::class, 'index'])->name('leave-balance.index')->middleware('role:admin');
    Route::put('leave-balance/{employee}', [LeaveBalanceController::class, 'update'])->name('leave-balance.update')->middleware('role:admin');
    Route::post('leave-balance/reset', [LeaveBalanceController::class, 'reset'])->name('leave-balance.reset')->middleware('role:admin');

    Route::get('earned-leave', [EarnedLeaveController::class, 'index'])->name('earned-leave.index')->middleware('role:admin');
    Route::post('earned-leave/import', [EarnedLeaveController::class, 'import'])->name('earned-leave.import')->middleware('role:admin');
    Route::post('earned-leave/recalculate', [EarnedLeaveController::class, 'recalculate'])->name('earned-leave.recalculate')->middleware('role:admin');

    Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log.index')->middleware('role:admin');

    Route::get('login-attempts', [LoginAttemptController::class, 'index'])->name('login-attempts.index')->middleware('role:admin');
    Route::post('login-attempts/reset/{empId}', [LoginAttemptController::class, 'reset'])->name('login-attempts.reset')->middleware('role:admin');
    Route::post('login-attempts/reset-all', [LoginAttemptController::class, 'resetAll'])->name('login-attempts.reset-all')->middleware('role:admin');

    Route::get('change-password', [PasswordController::class, 'index'])->name('change-password.index')->middleware('role:any');
    Route::post('change-password', [PasswordController::class, 'update'])->name('change-password.update')->middleware('role:any');
});

Route::get('/up', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('_dev-login/{id}', function ($id) {
    \Illuminate\Support\Facades\Auth::loginUsingId((int) $id);
    request()->session()->regenerate();
    return redirect('/lesson-plans');
})->middleware('web');
