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
            ? SeniorCitizenIdCredential::query()->with('seniorCitizen')->where('verification_token_hash', hash('sha256', $token))->first() : null;

        return response()->view('senior-citizens.ids.verify', ['valid' => $credentials->valid($credential)], 200, [
            'Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer', 'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ]);
    }
}
