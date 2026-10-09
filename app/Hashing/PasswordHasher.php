<?php

namespace App\Hashing;

use Illuminate\Hashing\BcryptHasher;

class PasswordHasher extends BcryptHasher
{
    public function check(#[\SensitiveParameter] $value, $hashedValue, array $options = [])
    {
        if (strlen($value) > 72 || str_contains($value, "\0")) {
            return false;
        }

        if (is_string($hashedValue) && preg_match('/^[a-f0-9]{64}$/', $hashedValue)) {
            return hash_equals($hashedValue, hash('sha256', $value));
        }

        return parent::check($value, $hashedValue, $options);
    }
}
