<?php

namespace App\Services\Auth;

class EmployeeId
{
    public const PATTERN = '/\A[0-9]{4}-[0-9]{4}\z/';

    public static function normalize(string $value): string
    {
        return trim($value);
    }
}
