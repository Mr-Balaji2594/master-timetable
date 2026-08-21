<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EarnedLeaveCredit extends Model
{
    protected $table = 'earned_leave_credits';
    public $timestamps = false;

    protected $fillable = [
        'employee_id', 'credit_date', 'consecutive_days',
    ];

    protected function casts(): array
    {
        return [
            'credit_date' => 'date',
            'consecutive_days' => 'integer',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
