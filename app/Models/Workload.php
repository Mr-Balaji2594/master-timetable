<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workload extends Model
{
    public $timestamps = false;

    protected $fillable = ['employee_id', 'total_hours', 'period_week', 'computed_date'];

    protected function casts(): array
    {
        return [
            'total_hours' => 'decimal:1',
            'period_week' => 'decimal:1',
            'computed_date' => 'date',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
