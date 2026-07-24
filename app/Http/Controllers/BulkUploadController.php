<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TimetableSlot;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BulkUploadController extends Controller
{
    public function index()
    {
        return Inertia::render('BulkUpload/Index');
    }

    public function import()
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $data = request()->validate([
            'file' => 'required|file|mimes:csv,tsv,txt',
            'type' => 'required|string|in:employees,classes,subjects,timetable',
        ]);

        $file = request()->file('file');
        $handle = fopen($file->getPathname(), 'r');
        $delimiter = $file->getClientOriginalExtension() === 'tsv' ? "\t" : ',';

        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            return redirect()->back()->with('error', 'Invalid file format');
        }

        $header = array_map('trim', $header);
        $imported = 0;
        $errors = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row = array_map('trim', $row);
            $record = array_combine($header, $row);

            try {
                match ($data['type']) {
                    'employees' => $this->importEmployee($record),
                    'classes' => $this->importClass($record),
                    'subjects' => $this->importSubject($record),
                    'timetable' => $this->importTimetable($record),
                };
                $imported++;
            } catch (\Exception $e) {
                $errors[] = 'Row ' . ($imported + 2) . ': ' . $e->getMessage();
            }
        }

        fclose($handle);

        audit_log('bulk_import', "Imported {$imported} {$data['type']} records");

        return redirect()->back()->with('success', "Imported {$imported} records successfully." . (count($errors) ? ' Errors: ' . implode('; ', array_slice($errors, 0, 10)) : ''));
    }

    private function importEmployee(array $record): void
    {
        Employee::create([
            'emp_id' => $record['emp_id'],
            'department_id' => $record['department_id'],
            'name' => $record['name'],
            'designation' => $record['designation'] ?? null,
            'role' => $record['role'] ?? 'staff',
            'phone' => $record['phone'] ?? null,
            'email' => $record['email'] ?? null,
            'password' => bcrypt($record['password'] ?? 'password123'),
            'is_active' => filter_var($record['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    private function importClass(array $record): void
    {
        SchoolClass::create([
            'name' => $record['name'],
            'department_id' => $record['department_id'],
            'batch_year' => $record['batch_year'] ?? null,
            'year' => $record['year'] ?? null,
            'section' => $record['section'] ?? null,
        ]);
    }

    private function importSubject(array $record): void
    {
        Subject::create([
            'name' => $record['name'],
            'code' => $record['code'],
            'department_id' => $record['department_id'],
            'credits' => $record['credits'] ?? null,
            'lecture_hours_per_week' => $record['lecture_hours_per_week'] ?? null,
            'year' => $record['year'] ?? null,
            'sem' => $record['sem'] ?? null,
            'sem_mode' => $record['sem_mode'] ?? null,
        ]);
    }

    private function importTimetable(array $record): void
    {
        TimetableSlot::create([
            'class_id' => $record['class_id'],
            'subject_id' => $record['subject_id'],
            'employee_id' => $record['employee_id'],
            'day_of_week' => $record['day_of_week'],
            'period_no' => $record['period_no'],
            'semester' => $record['semester'] ?? null,
            'room_no' => $record['room_no'] ?? null,
            'combined_group_id' => $record['combined_group_id'] ?? null,
        ]);
    }
}
