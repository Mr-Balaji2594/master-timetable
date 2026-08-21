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
        'status', 'hod_approved_by', 'hod_approved_at',
        'principal_approved_by', 'principal_approved_at',
    ];

    protected function casts(): array
    {
        return [
            'hod_approved_at' => 'datetime',
            'principal_approved_at' => 'datetime',
        ];
    }

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

    public function hodApprover()
    {
        return $this->belongsTo(Employee::class, 'hod_approved_by');
    }

    public function principalApprover()
    {
        return $this->belongsTo(Employee::class, 'principal_approved_by');
    }
}
