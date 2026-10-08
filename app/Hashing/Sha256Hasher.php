<?php

namespace App\Hashing;

use Illuminate\Contracts\Hashing\Hasher as HasherContract;

class Sha256Hasher implements HasherContract
{
    public function info($hashedValue): array
    {
        return [
            'algo' => is_string($hashedValue) && preg_match('/^[a-f0-9]{64}$/', $hashedValue) ? 'sha256' : null,
            'algoName' => 'SHA-256',
            'options' => [],
        ];
    }

    public function make(#[\SensitiveParameter] $value, array $options = []): string
    {
        return hash('sha256', $value);
    }

    public function check(#[\SensitiveParameter] $value, $hashedValue, array $options = []): bool
    {
        if (! is_string($hashedValue) || ! preg_match('/^[a-f0-9]{64}$/', $hashedValue)) {
            return false;
        }

        return hash_equals($hashedValue, $this->make($value));
    }

    public function needsRehash($hashedValue, array $options = []): bool
    {
        return false;
    }
}
