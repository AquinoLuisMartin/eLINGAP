<?php

namespace App\Services\SeniorCitizens;

use Dompdf\Dompdf;

class IdCardTypography
{
    public function fontSize(string $text, float $widthMm, float $maximum, float $minimum, string $font = 'DejaVu Sans', string $style = 'bold', bool $uppercase = true): float
    {
        $metrics = (new Dompdf)->getFontMetrics();
        $sample = $uppercase ? mb_strtoupper($text) : $text;
        $width = $metrics->getTextWidth($sample, $metrics->getFont($font, $style), $maximum);
        if ($width <= 0) {
            return $maximum;
        }

        return max($minimum, min($maximum, floor($maximum * ($widthMm * 72 / 25.4) / $width * 10) / 10));
    }
}
