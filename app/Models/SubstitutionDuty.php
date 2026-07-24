<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubstitutionDuty extends Model
{
    protected $table = 'substitution_duties';
    public $timestamps = false;

    protected $fillable = [
        'original_employee_id', 'substitute_employee_id',
        'class_id', 'subject_id', 'day_of_week', 'period_no',
        'leave_date', 'status', 'compensation_hours',
    ];

    protected function casts(): array
    {
        return [
            'leave_date' => 'date',
            'day_of_week' => 'integer',
            'period_no' => 'integer',
            'compensation_hours' => 'decimal:1',
        ];
    }

    public function originalEmployee()
    {
        return $this->belongsTo(Employee::class, 'original_employee_id');
    }

    public function substituteEmployee()
    {
        return $this->belongsTo(Employee::class, 'substitute_employee_id');
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
