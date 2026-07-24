<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TimetableSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function subjects(Request $request)
    {
        $employeeId = $request->get('employee_id');
        $employee = Employee::findOrFail($employeeId);

        $subjects = $employee->subjects()->get();

        if ($subjects->isEmpty()) {
            $subjects = Subject::where('department_id', $employee->department_id)->get();
        }

        return response()->json($subjects);
    }

    public function classes(Request $request)
    {
        $employeeId = $request->get('employee_id');
        $employee = Employee::findOrFail($employeeId);

        $subjectIds = $employee->subjects()->pluck('subjects.id');

        if ($subjectIds->isEmpty()) {
            $subjectIds = Subject::where('department_id', $employee->department_id)->pluck('id');
        }

        $classIds = TimetableSlot::whereIn('subject_id', $subjectIds)
            ->where('employee_id', $employeeId)
            ->pluck('class_id')
            ->unique();

        $classes = SchoolClass::whereIn('id', $classIds)->orWhere('department_id', $employee->department_id)->get();

        return response()->json($classes);
    }

    public function employees(Request $request)
    {
        $query = Employee::where('is_active', true);

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        return response()->json($query->get(['id', 'emp_id', 'name', 'department_id']));
    }
}
