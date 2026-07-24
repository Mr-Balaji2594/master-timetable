<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSubject extends Model
{
    protected $table = 'employee_subjects';
    public $timestamps = false;

    protected $fillable = ['employee_id', 'subject_id'];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
