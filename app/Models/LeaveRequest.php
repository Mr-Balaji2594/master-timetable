<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $table = 'leave_requests';
    public $timestamps = false;

    protected $fillable = [
        'employee_id', 'leave_date', 'due_date', 'nature', 'days',
        'reason', 'status',
        'hod_approved_by', 'hod_approved_at',
        'principal_approved_by', 'principal_approved_at',
    ];

    protected function casts(): array
    {
        return [
            'leave_date' => 'date',
            'due_date' => 'date',
            'days' => 'integer',
            'hod_approved_at' => 'datetime',
            'principal_approved_at' => 'datetime',
        ];
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
