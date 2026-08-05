<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workload extends Model
{
    protected $table = 'workload';
    public $timestamps = false;

    protected $fillable = ['year', 'sem_mode', 'department_id', 'subject_name', 'total_hours'];

    protected function casts(): array
    {
        return [
            'total_hours' => 'decimal:1',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
