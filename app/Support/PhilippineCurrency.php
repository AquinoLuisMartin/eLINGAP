<?php

namespace App\Support;

class PhilippineCurrency
{
    public static function format(string|int|float $amount): string
    {
        return '₱'.number_format((float) $amount, 2);
    }
}
