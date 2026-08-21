<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TimetableSlot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BulkUploadController extends Controller
{
    private const ENTITIES = ['departments', 'employees', 'classes', 'subjects', 'timetable'];

    public function index()
    {
        return Inertia::render('BulkUpload/Index', [
            'entities' => self::ENTITIES,
            'templates' => [
                'departments' => ['name', 'code', 'branch_code', 'staff_count'],
                'employees' => ['emp_id', 'name', 'department', 'designation', 'mode', 'role', 'email', 'phone', 'password', 'is_active'],
                'classes' => ['name', 'department', 'program_type', 'batch_year', 'year', 'block', 'floor'],
                'subjects' => ['name', 'code', 'department', 'credits', 'lecture_hours_per_week', 'year', 'sem', 'sem_mode', 'is_common'],
                'timetable' => ['class', 'subject', 'employee', 'day_of_week', 'period_no', 'semester', 'room_no'],
            ],
        ]);
    }

    public function import()
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $data = request()->validate([
            'file' => 'required|file|mimes:csv,tsv,txt',
            'type' => 'required|string|in:' . implode(',', self::ENTITIES),
            'skip_duplicates' => 'nullable|boolean',
        ]);

        $file = request()->file('file');
        $delimiter = $this->detectDelimiter($file->getClientOriginalExtension());

        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            return redirect()->back()->with('error', 'Invalid or empty file');
        }

        $header = array_map('trim', $header);
        $required = $this->requiredColumns($data['type']);
        $missing = array_diff($required, $header);
        if ($missing) {
            fclose($handle);
            return redirect()->back()->with('error', 'Missing required column(s): ' . implode(', ', $missing));
        }

        $skipDuplicates = filter_var($data['skip_duplicates'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            $line = 1;
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $line++;
                $row = array_map('trim', $row);
                $record = array_combine($header, $row);

                try {
                    $result = $this->importRecord($data['type'], $record);
                    if ($result === 'skipped') {
                        $skipped++;
                    } else {
                        $imported++;
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Row ' . $line . ': ' . $e->getMessage();
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);

        audit_log('bulk_import', "Imported {$imported} {$data['type']} records");

        $message = "Imported {$imported} {$data['type']} record(s).";
        if ($skipped) {
            $message .= " Skipped {$skipped} duplicate(s).";
        }
        if ($errors) {
            $message .= ' Errors: ' . implode(' | ', array_slice($errors, 0, 10));
        }

        return redirect()->back()->with('success', $message);
    }

    private function detectDelimiter(string $extension): string
    {
        if ($extension === 'tsv') {
            return "\t";
        }
        if ($extension === 'csv') {
            return ',';
        }
        // .txt: auto-detect tab vs comma from the first line
        $handle = fopen(request()->file('file')->getPathname(), 'r');
        $first = fgets($handle);
        fclose($handle);
        $tabs = substr_count((string) $first, "\t");
        $commas = substr_count((string) $first, ',');
        return $tabs > $commas ? "\t" : ',';
    }

    private function requiredColumns(string $type): array
    {
        return match ($type) {
            'departments' => ['name'],
            'employees' => ['emp_id', 'name', 'department'],
            'classes' => ['name', 'department'],
            'subjects' => ['name', 'code', 'department'],
            'timetable' => ['class', 'subject', 'employee', 'day_of_week', 'period_no'],
            default => [],
        };
    }

    private function importRecord(string $type, array $record): string
    {
        return match ($type) {
            'departments' => $this->importDepartment($record),
            'employees' => $this->importEmployee($record),
            'classes' => $this->importClass($record),
            'subjects' => $this->importSubject($record),
            'timetable' => $this->importTimetable($record),
        };
    }

    private function resolveDepartment(string $value): Department
    {
        $value = trim($value);
        $dept = Department::where('name', $value)
            ->orWhere('code', $value)
            ->first();
        if (!$dept) {
            throw new \Exception("Department '{$value}' not found");
        }
        return $dept;
    }

    private function importDepartment(array $record): string
    {
        $name = trim($record['name']);
        $existing = Department::where('name', $name)->first();
        if ($existing) {
            return 'skipped';
        }

        Department::create([
            'name' => $name,
            'code' => trim($record['code'] ?? '') ?: strtoupper(substr($name, 0, 3)),
            'branch_code' => $record['branch_code'] ?? null,
            'staff_count' => $record['staff_count'] ?? null,
        ]);
        return 'created';
    }

    private function importEmployee(array $record): string
    {
        $empId = trim($record['emp_id']);
        $existing = Employee::where('emp_id', $empId)->first();
        if ($existing) {
            return 'skipped';
        }

        $dept = $this->resolveDepartment($record['department']);

        Employee::create([
            'emp_id' => $empId,
            'department_id' => $dept->id,
            'name' => trim($record['name']),
            'designation' => $record['designation'] ?? null,
            'mode' => $record['mode'] ?? 'permanent',
            'role' => $record['role'] ?? 'staff',
            'email' => $record['email'] ?? null,
            'phone' => $record['phone'] ?? null,
            'password' => bcrypt($record['password'] ?? 'password123'),
            'is_active' => filter_var($record['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ]);
        return 'created';
    }

    private function importClass(array $record): string
    {
        $dept = $this->resolveDepartment($record['department']);
        $name = trim($record['name']);

        $existing = SchoolClass::where('name', $name)
            ->where('department_id', $dept->id)
            ->first();
        if ($existing) {
            return 'skipped';
        }

        SchoolClass::create([
            'name' => $name,
            'department_id' => $dept->id,
            'program_type' => $record['program_type'] ?? 'UG',
            'batch_year' => $record['batch_year'] ?? null,
            'year' => $record['year'] ?? null,
            'block' => $record['block'] ?? null,
            'floor' => $record['floor'] ?? null,
        ]);
        return 'created';
    }

    private function importSubject(array $record): string
    {
        $dept = $this->resolveDepartment($record['department']);
        $code = trim($record['code']);

        $existing = Subject::where('code', $code)->first();
        if ($existing) {
            return 'skipped';
        }

        Subject::create([
            'name' => trim($record['name']),
            'code' => $code,
            'department_id' => $dept->id,
            'credits' => $record['credits'] ?? null,
            'lecture_hours_per_week' => $record['lecture_hours_per_week'] ?? null,
            'year' => $record['year'] ?? null,
            'sem' => $record['sem'] ?? null,
            'sem_mode' => $record['sem_mode'] ?? null,
            'is_common' => filter_var($record['is_common'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);
        return 'created';
    }

    private function importTimetable(array $record): string
    {
        $class = SchoolClass::where('name', trim($record['class']))->first();
        if (!$class) {
            throw new \Exception("Class '{$record['class']}' not found");
        }
        $subject = Subject::where('code', trim($record['subject']))->orWhere('name', trim($record['subject']))->first();
        if (!$subject) {
            throw new \Exception("Subject '{$record['subject']}' not found");
        }
        $employee = Employee::where('emp_id', trim($record['employee']))->first();
        if (!$employee) {
            throw new \Exception("Employee '{$record['employee']}' not found");
        }

        $day = $this->normalizeDay($record['day_of_week']);
        $period = (int) trim($record['period_no']);

        $existing = TimetableSlot::where('class_id', $class->id)
            ->where('day_of_week', $day)
            ->where('period_no', $period)
            ->first();
        if ($existing) {
            return 'skipped';
        }

        TimetableSlot::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'employee_id' => $employee->id,
            'day_of_week' => $day,
            'period_no' => $period,
            'semester' => $record['semester'] ?? null,
            'room_no' => $record['room_no'] ?? null,
        ]);
        return 'created';
    }

    private function normalizeDay(string $day): int
    {
        $day = strtolower(trim($day));
        $map = [
            '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6,
            'mon' => 1, 'monday' => 1,
            'tue' => 2, 'tues' => 2, 'tuesday' => 2,
            'wed' => 3, 'wednesday' => 3,
            'thu' => 4, 'thur' => 4, 'thurs' => 4, 'thursday' => 4,
            'fri' => 5, 'friday' => 5,
            'sat' => 6, 'saturday' => 6,
        ];
        if (!isset($map[$day])) {
            throw new \Exception("Invalid day '{$day}' (use 1-6 or Mon-Sat)");
        }
        return $map[$day];
    }
}
