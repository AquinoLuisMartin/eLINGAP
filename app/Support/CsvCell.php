<?php

namespace App\Support;

final class CsvCell
{
    public static function escape(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^(?:[\x00-\x20]*[=+\-@]|[\t\r\n])/', $value)
            ? "'".$value : $value;
    }
}
