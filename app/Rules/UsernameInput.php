<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UsernameInput implements ValidationRule
{
    public function __construct(private ?int $ignore = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/\A[a-z0-9]+(?:\.[a-z0-9]+)*\z/', $value) || strlen($value) < 5 || strlen($value) > 40) {
            $fail('Use 5 to 40 lowercase letters, numbers and single periods, without a period at either end.');

            return;
        }
        if (User::query()->whereRaw('lower(username) = ?', [$value])->when($this->ignore, fn ($query) => $query->whereKeyNot($this->ignore))->exists()) {
            $fail('This username is already in use. Choose another username.');
        }
    }
}
