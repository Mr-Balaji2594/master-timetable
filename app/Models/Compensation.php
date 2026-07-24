<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compensation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'substitute_employee_id', 'original_employee_id',
        'class_id', 'subject_id', 'day_of_week', 'period_no',
        'leave_date', 'compensation_date', 'compensation_period', 'status',
    ];

    protected function casts(): array
    {
        return [
            'leave_date' => 'date',
            'compensation_date' => 'date',
            'day_of_week' => 'integer',
            'period_no' => 'integer',
            'compensation_period' => 'integer',
        ];
    }

    public function substituteEmployee()
    {
        return $this->belongsTo(Employee::class, 'substitute_employee_id');
    }

    public function originalEmployee()
    {
        return $this->belongsTo(Employee::class, 'original_employee_id');
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
