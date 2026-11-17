<?php

namespace App\Services\SeniorCitizens;

class TransferAddress
{
    public static function current(?string $address, ?string $barangay, array $office): string
    {
        $locality = [];
        foreach ([$barangay, $office['municipality'] ?? null, $office['province'] ?? null] as $part) {
            $part = preg_replace('/\s+/u', ' ', trim($part ?? ''));
            if ($part !== '') {
                $locality[mb_strtolower($part)] ??= $part;
            }
        }
        $details = [];
        foreach (explode(',', $address ?? '') as $part) {
            $part = preg_replace('/\s+/u', ' ', trim($part));
            if ($part !== '' && ! array_key_exists(mb_strtolower($part), $locality)) {
                $details[] = $part;
            }
        }

        return implode(', ', [...$details, ...array_values($locality)]);
    }
}
