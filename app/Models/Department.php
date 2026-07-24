<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'code', 'hod_id', 'branch_code', 'staff_count'];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id');
    }

    public function hod()
    {
        return $this->belongsTo(Employee::class, 'hod_id');
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class, 'department_id');
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class, 'department_id');
    }
}
