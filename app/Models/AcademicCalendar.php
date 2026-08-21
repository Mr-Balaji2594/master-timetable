<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicCalendar extends Model
{
    protected $connection = 'attendance';

    protected $table = 'academic_calendar';

    protected $fillable = [
        'calendar_date', 'session', 'academic_year',
        'is_working_day', 'day_order', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'calendar_date' => 'date',
            'is_working_day' => 'boolean',
        ];
    }
}