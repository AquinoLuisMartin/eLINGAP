<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class NewPassword implements ValidationRule
{
    private static ?array $common = null;

    public function __construct(private ?User $user = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || mb_strlen($value) < 15 || mb_strlen($value) > 128 || str_contains($value, "\0")) {
            $fail('Use 15 to 128 characters without null characters. Spaces and special characters are allowed.');

            return;
        }
        self::$common ??= array_fill_keys(array_map('mb_strtolower', file(resource_path('security/common-passwords.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)), true);
        if (isset(self::$common[mb_strtolower($value)])) {
            $fail('Choose a password that is not commonly used.');
        }
        if (! $this->user) {
            return;
        }
        if ($this->user->password_hash && Hash::check($value, $this->user->password_hash)) {
            $fail('Choose a password different from your current password.');
        }
        $normalize = fn ($text) => preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($text)));
        $password = $normalize($value);
        foreach ([$this->user->username, $this->user->full_name] as $identifier) {
            $identifier = $normalize($identifier);
            if ($identifier !== '' && preg_match('/\A'.preg_quote($identifier, '/').'[0-9]*\z/', $password)) {
                $fail('Choose a password that is not based on your username or name.');

                return;
            }
        }
    }
}
