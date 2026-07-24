<?php

use App\Http\Controllers\Api\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('get-employee-subjects', [EmployeeController::class, 'subjects'])->name('api.employee.subjects');
    Route::get('get-employee-classes', [EmployeeController::class, 'classes'])->name('api.employee.classes');
    Route::get('employees', [EmployeeController::class, 'employees'])->name('api.employees');
});
