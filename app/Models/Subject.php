<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name', 'code', 'department_id', 'credits',
        'lecture_hours_per_week', 'year', 'sem', 'sem_mode', 'is_common',
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'lecture_hours_per_week' => 'integer',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_subjects', 'subject_id', 'employee_id');
    }

    public function timetableSlots()
    {
        return $this->hasMany(TimetableSlot::class, 'subject_id');
    }
}
