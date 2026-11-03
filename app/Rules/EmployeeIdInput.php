<?php

namespace App\Rules;

use App\Models\User;
use App\Services\Auth\EmployeeId;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmployeeIdInput implements ValidationRule
{
    public function __construct(private ?int $ignore = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match(EmployeeId::PATTERN, $value)) {
            $fail('Enter the official employee ID in YYYY-NNNN format, for example 2026-0001.');

            return;
        }
        if (User::query()->where('employee_id', $value)->when($this->ignore, fn ($query) => $query->whereKeyNot($this->ignore))->exists()) {
            $fail('This employee ID is already assigned to another account.');
        }
    }
}
