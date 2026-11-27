<?php

namespace App\Support;

use Illuminate\Support\Str;

class PlaceName
{
    public static function comparisonKey(string $name): string
    {
        $key = mb_strtolower(Str::squish($name));

        return preg_replace('/\b(?:santa|sta\.?) (?=\p{L})/u', 'sta. ', $key) ?? $key;
    }
}
