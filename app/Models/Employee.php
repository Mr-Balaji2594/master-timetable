<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Employee extends Authenticatable
{
    public $timestamps = false;

    protected $fillable = [
        'emp_id', 'department_id', 'name', 'designation', 'mode',
        'total_leave_per_year', 'casual_leave_limit', 'medical_leave_limit',
        'onduty_leave_limit', 'early_permission_limit', 'late_permission_limit', 'deputation_limit',
        'casual_leave_availed', 'medical_leave_availed', 'onduty_leave_availed',
        'early_permission_availed', 'late_permission_availed', 'deputation_availed',
        'earned_leave_limit', 'earned_leave_availed',
        'role', 'password', 'is_active', 'must_change_password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'total_leave_per_year' => 'integer',
            'casual_leave_limit' => 'integer',
            'medical_leave_limit' => 'integer',
            'onduty_leave_limit' => 'integer',
            'early_permission_limit' => 'integer',
            'late_permission_limit' => 'integer',
            'deputation_limit' => 'integer',
            'casual_leave_availed' => 'decimal:1',
            'medical_leave_availed' => 'integer',
            'onduty_leave_availed' => 'integer',
            'early_permission_availed' => 'decimal:1',
            'late_permission_availed' => 'decimal:1',
            'deputation_availed' => 'integer',
            'earned_leave_limit' => 'integer',
            'earned_leave_availed' => 'integer',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'employee_subjects', 'employee_id', 'subject_id');
    }

    public function timetableSlots()
    {
        return $this->hasMany(TimetableSlot::class, 'employee_id');
    }

    public function lessonPlans()
    {
        return $this->hasMany(LessonPlan::class, 'employee_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'employee_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function isPrincipal(): bool
    {
        return $this->role === 'principal';
    }

    public function isVicePrincipal(): bool
    {
        return $this->role === 'vice_principal';
    }

    public function isHOD(): bool
    {
        return $this->role === 'hod';
    }

    public function isManagement(): bool
    {
        return in_array($this->role, ['admin', 'super_admin', 'principal', 'vice_principal', 'hod']);
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function getDeptIdAttribute()
    {
        return $this->department_id;
    }

    public function getDeptNameAttribute()
    {
        return $this->department?->name;
    }
}
