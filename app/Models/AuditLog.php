<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    public $timestamps = false;

    protected $fillable = ['user_id', 'emp_id', 'action', 'details', 'ip_address', 'user_agent'];
}
