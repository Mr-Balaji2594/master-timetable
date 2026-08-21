<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    protected bool $requireSpecial = true;

    public function __construct(bool $requireSpecial = true)
    {
        $this->requireSpecial = $requireSpecial;
    }

    public function validate($attribute, $value, Closure $fail): void
    {
        $value = (string) $value;

        if (strlen($value) < 8) {
            $fail('The :attribute must be at least 8 characters long.');
        }

        if (!preg_match('/[A-Za-z]/', $value)) {
            $fail('The :attribute must contain at least one letter.');
        }

        if (!preg_match('/[0-9]/', $value)) {
            $fail('The :attribute must contain at least one number.');
        }

        if ($this->requireSpecial && !preg_match('/[^A-Za-z0-9\s]/', $value)) {
            $fail('The :attribute must contain at least one special character (e.g. !@#$%^&*).');
        }

        if (preg_match('/^(12345678|password|password123|qwertyui|admin123|letmein|welcome1|iloveyou)$/i', $value)) {
            $fail('The :attribute is too common. Please choose a stronger password.');
        }
    }
}