<?php

namespace App\Services\SeniorCitizens;

use App\Enums\SeniorCitizenStatus;
use App\Models\SeniorCitizen;
use App\Models\SeniorCitizenIdCredential;
use App\Services\Benefits\BenefitAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class IdCredential
{
    public function createForIssued(SeniorCitizen $senior): SeniorCitizenIdCredential
    {
        Gate::authorize('manageId', $senior);

        return DB::transaction(function () use ($senior) {
            $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($senior->id);
            abort_unless($senior->id_generated_at && $senior->osca_id_number, 422, 'Record ID issuance first.');
            abort_unless($senior->verified_at && in_array($senior->status, [SeniorCitizenStatus::Verified, SeniorCitizenStatus::Archived], true), 422, 'Verify the record before preparing its ID.');
            abort_if($senior->died_on || $senior->death_declared_at, 422, 'A deceased record cannot receive an ID.');

            return $this->ensure($senior);
        });
    }

    // Call within the transaction that locks the senior record.
    public function ensure(SeniorCitizen $senior): SeniorCitizenIdCredential
    {
        $existing = SeniorCitizenIdCredential::query()->where('senior_citizen_id', $senior->id)->first();
        if ($existing) {
            return $existing;
        }
        $token = bin2hex(random_bytes(32));
        $credential = SeniorCitizenIdCredential::create([
            'senior_citizen_id' => $senior->id,
            'verification_token_hash' => hash('sha256', $token),
            'verification_token' => $token,
            'created_by' => auth()->id(),
        ]);
        BenefitAudit::record('senior_citizen.qr_enabled', $senior);

        return $credential;
    }

    public function svg(SeniorCitizenIdCredential $credential): string
    {
        $url = rtrim(config('app.url'), '/').route('id.verify', ['token' => $credential->verification_token], false);

        return (string) QrCode::format('svg')->size(300)->margin(4)->generate($url);
    }

    public function valid(?SeniorCitizenIdCredential $credential): bool
    {
        $senior = $credential?->seniorCitizen;

        return $senior && $senior->id_generated_at && $senior->osca_id_number
            && $senior->verified_at && ! $senior->died_on && ! $senior->death_declared_at
            && ! in_array($senior->status, [SeniorCitizenStatus::Deceased, SeniorCitizenStatus::Pending, SeniorCitizenStatus::Transferred], true);
    }
}
