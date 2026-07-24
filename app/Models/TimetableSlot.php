<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetableSlot extends Model
{
    protected $table = 'timetable';
    public $timestamps = false;

    protected $fillable = [
        'class_id', 'subject_id', 'employee_id',
        'day_of_week', 'period_no', 'semester', 'combined_group_id', 'room_no',
    ];

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
