<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $table = 'classes';
    public $timestamps = false;

    protected $fillable = ['name', 'department_id', 'batch_year', 'year'];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function timetableSlots()
    {
        return $this->hasMany(TimetableSlot::class, 'class_id');
    }

    public function lessonPlans()
    {
        return $this->hasMany(LessonPlan::class, 'class_id');
    }
}
