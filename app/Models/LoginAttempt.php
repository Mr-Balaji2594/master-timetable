<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    protected $table = 'login_attempts';
    public $timestamps = false;

    protected $fillable = ['emp_id', 'ip_address', 'success'];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
        ];
    }
}
