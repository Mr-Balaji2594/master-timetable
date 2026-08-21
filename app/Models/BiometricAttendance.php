<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiometricAttendance extends Model
{
    protected $table = 'biometric_attendance';
    public $timestamps = false;

    protected $fillable = [
        'employee_id', 'attendance_date', 'check_in', 'check_out',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
