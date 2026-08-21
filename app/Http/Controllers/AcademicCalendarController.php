<?php

namespace App\Http\Controllers;

use App\Models\AcademicCalendar;
use Inertia\Inertia;

class AcademicCalendarController extends Controller
{
    public function index()
    {
        $years = AcademicCalendar::query()
            ->select('academic_year', 'session')
            ->distinct()
            ->orderBy('academic_year')
            ->orderBy('session')
            ->get()
            ->map(fn($y) => [
                'academic_year' => $y->academic_year,
                'session' => $y->session,
            ]);

        $academicYear = request('academic_year', $years->first()['academic_year'] ?? null);
        $session = request('session', $years->first()['session'] ?? null);

        $calendar = $academicYear && $session
            ? AcademicCalendar::query()
                ->where('academic_year', $academicYear)
                ->where('session', $session)
                ->orderBy('calendar_date')
                ->get()
                ->map(fn($d) => [
                    'calendar_date' => $d->calendar_date->format('Y-m-d'),
                    'session' => $d->session,
                    'academic_year' => $d->academic_year,
                    'is_working_day' => (bool) $d->is_working_day,
                    'day_order' => $d->day_order,
                    'remarks' => $d->remarks,
                ])
            : collect();

        return Inertia::render('AcademicCalendar/Index', [
            'years' => $years,
            'academicYear' => $academicYear,
            'session' => $session,
            'calendar' => $calendar,
        ]);
    }
}