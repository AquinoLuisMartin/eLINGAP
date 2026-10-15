<?php

namespace App\Services\Benefits;

use App\Models\BenefitSchedule;
use App\Models\SeniorCitizen;
use App\Services\SeniorCitizens\IdCredential;
use App\Services\SeniorCitizens\OscaIdNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SeniorEligibilityRecord
{
    public function __construct(private OscaIdNumber $numbers, private IdCredential $credentials) {}

    public function verify(SeniorCitizen $senior, bool $checked): void
    {
        Gate::authorize('verify', $senior);
        abort_unless($checked, 422, 'Confirm the registration documents first.');
        DB::transaction(function () use ($senior): void {
            $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($senior->id);
            abort_unless(in_array($senior->status->value, ['PENDING', 'VERIFIED']), 422);
            abort_if($senior->died_on || $senior->death_declared_at, 422, 'A deceased record cannot be verified.');
            abort_unless($senior->smsContact(), 422, 'A senior or representative mobile number is required.');
            $representative = $senior->applicant_type === 'REPRESENTATIVE' || filled($senior->representative_name) || filled($senior->representative_contact);
            if ($representative) {
                abort_unless($senior->representative_name && $senior->representative_relationship && $senior->documents()->where('document_type', 'AUTHORIZATION')->exists(), 422, 'Representative authorization is required.');
            }
            if ($senior->status->value === 'VERIFIED' && $senior->verified_at) {
                return;
            }
            $reason = $senior->review_reason;
            $senior->forceFill(['status' => 'VERIFIED', 'verified_at' => now(), 'verified_by' => auth()->id(), 'review_reason' => null, 'requirements_checks' => ['identity' => true, 'residency' => true, 'authorization' => $representative]])->save();
            $senior->histories()->create(['changed_by' => auth()->id(), 'action' => 'verified', 'changes' => ['status' => 'VERIFIED', 'resolved_review_reason' => $reason, 'requirements_checks' => $senior->requirements_checks]]);
            BenefitAudit::record('senior_citizen.verified', $senior);
        });
    }

    public function issueId(SeniorCitizen $senior): void
    {
        Gate::authorize('manageId', $senior);
        DB::transaction(function () use ($senior): void {
            $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($senior->id);
            abort_unless($senior->status->value === 'VERIFIED' && $senior->verified_at && $senior->smsContact(), 422, 'Verify the record before issuing its ID.');
            abort_if($senior->id_generated_at, 409, 'ID issuance is already recorded.');
            $senior->forceFill(['osca_id_number' => $senior->osca_id_number ?: $this->numbers->next(), 'id_generated_at' => now()])->save();
            $this->credentials->ensure($senior);
            $senior->histories()->create(['changed_by' => auth()->id(), 'action' => 'id_generated', 'changes' => ['issued_at' => now()->toIso8601String()]]);
            BenefitAudit::record('senior_citizen.id_generated', $senior);
        });
    }

    public function classify(SeniorCitizen $senior, array $data): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        $data = validator($data, [
            'is_bedridden' => ['required', 'boolean'], 'is_indigent' => ['required', 'boolean'],
            'has_national_pension' => ['required', 'boolean'], 'has_local_pension' => ['required', 'boolean'],
            'tss_request' => ['nullable', 'string', 'min:5', 'max:500'],
        ])->validate();
        DB::transaction(function () use ($senior, $data): void {
            $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($senior->id);
            abort_unless($senior->status->value === 'VERIFIED', 422, 'Only verified records may be classified.');
            abort_unless($senior->verified_at && $senior->id_generated_at && $senior->osca_id_number, 422, 'Generate the OSCA ID before assigning benefits or classifications.');
            $fields = ['is_bedridden', 'is_indigent', 'has_national_pension', 'has_local_pension'];
            $before = $senior->only($fields);
            $senior->forceFill([
                ...$data, 'classification_verified_at' => now(), 'classification_verified_by' => auth()->id(),
                'tss_verified_at' => empty($data['tss_request']) ? null : now(),
                'tss_verified_by' => empty($data['tss_request']) ? null : auth()->id(),
            ])->save();
            $changes = ['from' => $before, 'to' => $senior->only($fields), 'tss_verified' => $senior->tss_verified_at !== null];
            $senior->histories()->create(['changed_by' => auth()->id(), 'action' => 'classifications_verified', 'changes' => $changes]);
            BenefitAudit::record('senior_citizen.classifications_verified', $senior, $changes);
        });
    }
}
