<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonPlan extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'employee_id', 'class_id', 'subject_id',
        'day', 'period', 'semester', 'topic', 'description',
        'unit', 'plan_date', 'status',
        'hod_approved_by', 'hod_approved_at',
        'principal_approved_by', 'principal_approved_at',
    ];

    protected function casts(): array
    {
        return [
            'plan_date' => 'date',
            'hod_approved_at' => 'datetime',
            'principal_approved_at' => 'datetime',
            'day' => 'integer',
            'period' => 'integer',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
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
