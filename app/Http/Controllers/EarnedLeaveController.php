<?php

namespace App\Http\Controllers;

use App\Models\BiometricAttendance;
use App\Models\EarnedLeaveCredit;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EarnedLeaveController extends Controller
{
    private const EL_THRESHOLD = 22;

    public function index()
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $employees = Employee::whereIn('role', ['staff', 'hod'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($e) => array_merge(
                [
                    'id' => $e->id,
                    'emp_id' => $e->emp_id,
                    'name' => $e->name,
                    'department_id' => $e->department_id,
                    'dept_name' => $e->department?->name,
                    'earned_leave_limit' => $e->earned_leave_limit,
                    'earned_leave_availed' => $e->earned_leave_availed,
                    'available' => max(0, $e->earned_leave_limit - $e->earned_leave_availed),
                ],
                $this->processEmployee($e, false)
            ));

        $recentCredits = EarnedLeaveCredit::with('employee')
            ->orderByDesc('credit_date')
            ->take(20)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'employee' => $c->employee ? ['id' => $c->employee->id, 'emp_id' => $c->employee->emp_id, 'name' => $c->employee->name] : null,
                'credit_date' => $c->credit_date,
                'consecutive_days' => $c->consecutive_days,
            ]);

        return Inertia::render('EarnedLeave/Index', [
            'employees' => $employees,
            'recentCredits' => $recentCredits,
            'elThreshold' => self::EL_THRESHOLD,
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
        ]);

        $file = request()->file('file');
        $delimiter = $this->detectDelimiter($file->getClientOriginalExtension());

        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            return redirect()->back()->with('error', 'Invalid or empty file');
        }

        $header = array_map(fn($h) => strtolower(trim((string) $h)), $header);
        $map = $this->mapColumns($header);

        $required = ['emp_id', 'date'];
        foreach ($required as $col) {
            if (!isset($map[$col])) {
                fclose($handle);
                return redirect()->back()->with('error', 'Missing required column: ' . $col . ' (expected emp_id, attendance_date, check_in, check_out)');
            }
        }

        $imported = 0;
        $updated = 0;
        $errors = [];
        $affectedEmployeeIds = [];

        DB::beginTransaction();
        try {
            $line = 1;
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $line++;
                $row = array_map(fn($v) => trim((string) $v), $row);

                try {
                    $empId = $row[$map['emp_id']] ?? '';
                    $dateRaw = $row[$map['date']] ?? '';

                    $employee = Employee::where('emp_id', $empId)->first();
                    if (!$employee) {
                        throw new \Exception("Employee '{$empId}' not found");
                    }

                    $date = $this->parseDate($dateRaw);
                    if (!$date) {
                        throw new \Exception("Invalid date '{$dateRaw}'");
                    }

                    $checkIn = isset($map['check_in']) ? ($row[$map['check_in']] ?? null) : null;
                    $checkOut = isset($map['check_out']) ? ($row[$map['check_out']] ?? null) : null;

                    $existing = BiometricAttendance::where('employee_id', $employee->id)
                        ->where('attendance_date', $date)
                        ->first();

                    $data = [
                        'employee_id' => $employee->id,
                        'attendance_date' => $date,
                        'check_in' => $checkIn ?: null,
                        'check_out' => $checkOut ?: null,
                    ];

                    if ($existing) {
                        $existing->update($data);
                        $updated++;
                    } else {
                        BiometricAttendance::create($data);
                        $imported++;
                    }

                    $affectedEmployeeIds[] = $employee->id;
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

        $creditsAdded = 0;
        if (count($affectedEmployeeIds) > 0) {
            $employees = Employee::whereIn('id', array_unique($affectedEmployeeIds))->get();
            foreach ($employees as $employee) {
                $creditsAdded += $this->processEmployee($employee, true)['credits_added'];
            }
        }

        audit_log('biometric_import', "Imported {$imported} new, updated {$updated} biometric records, {$creditsAdded} EL credited");

        $message = "Imported {$imported} new and updated {$updated} biometric attendance record(s).";
        if ($creditsAdded) {
            $message .= " {$creditsAdded} earned leave(s) credited.";
        }
        if ($errors) {
            $message .= ' Errors: ' . implode(' | ', array_slice($errors, 0, 10));
        }

        return redirect()->back()->with('success', $message);
    }

    public function recalculate()
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $employees = Employee::whereIn('role', ['staff', 'hod'])->where('is_active', true)->get();
        $creditsAdded = 0;
        foreach ($employees as $employee) {
            $creditsAdded += $this->processEmployee($employee, true)['credits_added'];
        }

        audit_log('el_recalculate', "Recalculated earned leave, {$creditsAdded} EL credited");

        $message = "Earned leave recalculated for {$employees->count()} employee(s).";
        if ($creditsAdded) {
            $message .= " {$creditsAdded} earned leave(s) credited.";
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Walk the attendance calendar for an employee and optionally credit EL.
     *
     * - Sundays are skipped (non-working day, neither counted nor breaking the streak).
     * - An approved CL/ML leave on a Mon-Sat day resets the streak.
     * - A Mon-Sat day with no biometric punch and no approved CL/ML resets the streak.
     * - Every $threshold consecutive working days credits 1 EL.
     *
     * @return array{attendance_days: int, current_streak: int, credits_added: int}
     */
    private function processEmployee(Employee $employee, bool $doCredit): array
    {
        $punchDays = BiometricAttendance::where('employee_id', $employee->id)
            ->pluck('attendance_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();
        sort($punchDays);

        if (empty($punchDays)) {
            return ['attendance_days' => 0, 'current_streak' => 0, 'credits_added' => 0];
        }

        $leaveDates = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereIn('nature', ['casual', 'medical'])
            ->get()
            ->flatMap(function ($l) {
                $start = Carbon::parse($l->leave_date);
                $end = Carbon::parse($l->due_date);
                $dates = [];
                while ($start->lte($end)) {
                    $dates[] = $start->toDateString();
                    $start->addDay();
                }
                return $dates;
            })
            ->flip();

        $punchSet = array_flip($punchDays);

        $walk = Carbon::parse($punchDays[0]);
        $end = Carbon::parse($punchDays[count($punchDays) - 1]);

        $streak = 0;
        $currentStreak = 0;
        $creditsAdded = 0;

        while ($walk->lte($end)) {
            if ($walk->dayOfWeek === Carbon::SUNDAY) {
                $walk->addDay();
                continue;
            }

            $date = $walk->toDateString();

            if (isset($leaveDates[$date])) {
                $streak = 0;
            } elseif (isset($punchSet[$date])) {
                $streak++;
                if ($doCredit && $streak >= self::EL_THRESHOLD) {
                    $already = EarnedLeaveCredit::where('employee_id', $employee->id)
                        ->where('credit_date', $date)
                        ->exists();
                    if (!$already) {
                        EarnedLeaveCredit::create([
                            'employee_id' => $employee->id,
                            'credit_date' => $date,
                            'consecutive_days' => self::EL_THRESHOLD,
                        ]);
                        $employee->increment('earned_leave_limit');
                        $creditsAdded++;
                    }
                    $streak = 0;
                }
            } else {
                $streak = 0;
            }

            $currentStreak = $streak;
            $walk->addDay();
        }

        return [
            'attendance_days' => count($punchDays),
            'current_streak' => $currentStreak,
            'credits_added' => $creditsAdded,
        ];
    }

    private function detectDelimiter(string $extension): string
    {
        if ($extension === 'tsv') {
            return "\t";
        }
        if ($extension === 'csv') {
            return ',';
        }
        $handle = fopen(request()->file('file')->getPathname(), 'r');
        $first = fgets($handle);
        fclose($handle);
        $tabs = substr_count((string) $first, "\t");
        $commas = substr_count((string) $first, ',');
        return $tabs > $commas ? "\t" : ',';
    }

    private function mapColumns(array $header): array
    {
        $map = [];
        foreach ($header as $i => $col) {
            $key = null;
            if (in_array($col, ['emp_id', 'employee_id', 'employee_code', 'empcode', 'emp_code', 'id'])) {
                $key = 'emp_id';
            } elseif (in_array($col, ['attendance_date', 'date', 'att_date', 'punch_date', 'punching_date', 'work_date'])) {
                $key = 'date';
            } elseif (in_array($col, ['check_in', 'checkin', 'in_time', 'time_in', 'punch_in', 'in'])) {
                $key = 'check_in';
            } elseif (in_array($col, ['check_out', 'checkout', 'out_time', 'time_out', 'punch_out', 'out'])) {
                $key = 'check_out';
            }
            if ($key) {
                $map[$key] = $i;
            }
        }
        return $map;
    }

    private function parseDate(string $raw): ?string
    {
        $raw = trim($raw);
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'Y/m/d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $raw);
                if ($date && $date->format($format) === $raw) {
                    return $date->toDateString();
                }
            } catch (\Throwable $e) {
            }
        }
        return null;
    }
}
