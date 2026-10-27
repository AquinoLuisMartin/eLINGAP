<?php

namespace App\Hashing;

use Illuminate\Hashing\Argon2IdHasher;
use Illuminate\Hashing\BcryptHasher;

class PasswordHasher extends Argon2IdHasher
{
    public function check(#[\SensitiveParameter] $value, $hashedValue, array $options = [])
    {
        if (! is_string($value) || mb_strlen($value) > 128 || str_contains($value, "\0") || ! is_string($hashedValue)) {
            return false;
        }

        if (is_string($hashedValue) && preg_match('/^[a-f0-9]{64}$/', $hashedValue)) {
            return strlen($value) <= 72 && hash_equals($hashedValue, hash('sha256', $value));
        }

        $algorithm = password_get_info($hashedValue)['algoName'];
        if ($algorithm === 'bcrypt') {
            return strlen($value) <= 72 && (new BcryptHasher(['verify' => true]))->check($value, $hashedValue);
        }

        return $algorithm === 'argon2id' && parent::check($value, $hashedValue, $options);
    }
}
