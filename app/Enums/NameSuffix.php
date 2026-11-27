<?php

namespace App\Enums;

enum NameSuffix: string
{
    case Junior = 'Jr.';
    case Senior = 'Sr.';
    case Second = 'II';
    case Third = 'III';
    case Fourth = 'IV';
    case Fifth = 'V';

    public static function normalize(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);
        $upper = strtoupper($value);

        return match ($upper) {
            '' => null,
            'JR', 'JR.' => self::Junior->value,
            'SR', 'SR.' => self::Senior->value,
            default => self::tryFrom($upper)?->value ?? $value,
        };
    }
}
