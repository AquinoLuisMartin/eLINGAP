<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordInput implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && (mb_strlen($value) > 128 || str_contains($value, "\0"))) {
            $fail('The :attribute must be at most 128 characters and must not contain null characters.');
        }
    }
}
