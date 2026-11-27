<?php

namespace App\Http\Controllers\SeniorCitizens;

use App\Http\Controllers\Controller;
use App\Models\SeniorCitizenIdCredential;
use App\Services\SeniorCitizens\IdCredential;
use Symfony\Component\HttpFoundation\Response;

class PublicIdVerificationController extends Controller
{
    public function __invoke(string $token, IdCredential $credentials): Response
    {
        $credential = preg_match('/\A[a-f0-9]{64}\z/', $token)
            ? SeniorCitizenIdCredential::query()
                ->with('seniorCitizen:id,first_name,middle_name,last_name,name_suffix,osca_id_number,id_generated_at,verified_at,died_on,death_declared_at,status')
                ->where('verification_token_hash', hash('sha256', $token))
                ->first(['senior_citizen_id'])
            : null;
        $senior = $credentials->valid($credential) ? $credential?->seniorCitizen : null;

        return response()->view('senior-citizens.ids.verify', [
            'valid' => $senior !== null,
            'seniorName' => $senior?->full_name,
            'oscaIdNumber' => $senior?->osca_id_number,
        ], 200, [
            'Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer', 'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ]);
    }
}
