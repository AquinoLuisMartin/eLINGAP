<?php

namespace App\Services\SeniorCitizens;

use Dompdf\Dompdf;

class IdCardAddress
{
    public function format(string $address, string $barangay): array
    {
        $locality = [$barangay, 'Santa Maria', 'Bulacan'];
        $normalizedLocality = array_map(fn ($part) => mb_strtolower($part), $locality);
        $parts = preg_split('/\s*,\s*/', trim($address), -1, PREG_SPLIT_NO_EMPTY);
        $parts = array_filter($parts, fn ($part) => ! in_array(mb_strtolower($part), $normalizedLocality, true));
        $text = preg_replace('/\s+/', ' ', implode(', ', [...$parts, ...$locality]));
        $metrics = (new Dompdf)->getFontMetrics();
        $font = $metrics->getFont('DejaVu Sans', 'normal');

        foreach ([5.5, 5.0, 4.5] as $size) {
            $lines = [''];
            foreach (explode(' ', $text) as $word) {
                $last = array_key_last($lines);
                $candidate = ltrim($lines[$last].' '.$word);
                if ($metrics->getTextWidth($candidate, $font, $size) <= 68.2 * 72 / 25.4) {
                    $lines[$last] = $candidate;
                } else {
                    if ($metrics->getTextWidth($word, $font, $size) > 68.2 * 72 / 25.4) {
                        continue 2;
                    }
                    $lines[] = $word;
                }
            }
            if (count($lines) <= 3 && count($lines) * $metrics->getFontHeight($font, $size) * 1.05 + 0.45 * 72 / 25.4 <= 7 * 72 / 25.4) {
                return ['text' => $text, 'lines' => $lines, 'fontSize' => $size, 'fits' => true];
            }
        }

        return ['text' => $text, 'lines' => [], 'fontSize' => 4.5, 'fits' => false];
    }
}
