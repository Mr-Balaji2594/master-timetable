<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommonPaperAllocation extends Model
{
    protected $table = 'common_paper_allocations';
    public $timestamps = false;

    protected $fillable = ['subject_id', 'class_id'];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
