<?php

namespace App\Services\SeniorCitizens;

use App\Models\SeniorCitizen;
use Illuminate\Support\Facades\DB;

class OscaIdNumber
{
    public function next(): string
    {
        do {
            $issuedAt = now();
            $sequence = DB::table('osca_id_numbers')->insertGetId([
                'issued_year' => $issuedAt->year,
                'created_at' => $issuedAt,
            ]);
            $number = $issuedAt->format('y').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        } while (SeniorCitizen::query()->where('osca_id_number', $number)->exists());

        return $number;
    }
}
