<?php

namespace App\Services\SeniorCitizens;

use App\Models\IdPrintAttempt;
use App\Models\SeniorCitizen;
use App\Services\Benefits\BenefitAudit;
use App\Services\Benefits\SeniorEligibilityRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class IdPrinting
{
    public function __construct(private IdCardLayout $layout, private SeniorEligibilityRecord $records) {}

    public function generate(array $ids, string $paper, string $layout = 'single', bool $issuePending = false): array
    {
        return DB::transaction(function () use ($ids, $paper, $layout, $issuePending): array {
            if ($issuePending) {
                Gate::authorize('configureId', SeniorCitizen::class);
                SeniorRegistration::lockIdentityChanges();
            }
            $seniors = SeniorCitizen::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            abort_unless($seniors->count() === count($ids), 422, 'A selected registration is no longer available.');
            $attempts = [];
            foreach ($seniors as $senior) {
                Gate::authorize('manageId', $senior);
                $this->synchronizeLegacy($senior);
                if ($issuePending) {
                    abort_unless($senior->status->value === 'VERIFIED' && $senior->verified_at && $senior->smsContact(), 422, 'Verify the record before issuing or printing its ID.');
                    abort_if($senior->died_on || $senior->death_declared_at, 422, 'A deceased record cannot receive an ID.');
                }
                if ($senior->idPrintAttempts()->where('status', 'GENERATED')->exists()) {
                    throw ValidationException::withMessages(['printing' => 'Record the result in Printing Completion before generating another PDF.']);
                }
                $printed = $senior->idPrintAttempts()->where('status', 'CONFIRMED')->exists();
                $approval = $printed ? $senior->idReprintRequests()->where('status', 'APPROVED')->lockForUpdate()->first() : null;
                if ($printed && ! $approval) {
                    throw ValidationException::withMessages(['printing' => 'This ID has already been printed. Reprinting requires approval.']);
                }
                if ($issuePending && ! $senior->id_generated_at) {
                    $this->records->issueId($senior);
                    $senior->refresh();
                }
                $attempt = new IdPrintAttempt;
                $attempt->forceFill(['senior_citizen_id' => $senior->id, 'reprint_request_id' => $approval?->id,
                    'purpose' => $printed ? 'REPRINT' : 'FIRST_PRINT', 'status' => 'GENERATED',
                    'generated_by' => auth()->id(), 'generated_at' => now()])->save();
                $attempts[$senior->id] = $attempt;
            }
            $cards = $seniors->map(fn ($senior) => $this->layout->data($senior))->all();
            $pdf = $this->layout->sheetPdf($cards, $paper, $layout);
            foreach ($seniors as $senior) {
                $attempt = $attempts[$senior->id];
                BenefitAudit::record('senior_citizen.id_print_generated', $senior, ['paper' => $paper, 'purpose' => $attempt->purpose,
                    'attempt_id' => $attempt->id, 'batch_size' => count($cards), 'generated_at' => $attempt->generated_at->toIso8601String()]);
            }

            return [$seniors, $pdf, array_values($attempts)];
        });
    }

    public function complete(array $results): void
    {
        DB::transaction(function () use ($results): void {
            $attempts = IdPrintAttempt::query()->whereIn('id', array_column($results, 'attempt_id'))->orderBy('senior_citizen_id')->get();
            abort_unless($attempts->count() === count($results), 422, 'A selected print attempt is no longer available.');
            $outcomes = array_column($results, 'result', 'attempt_id');
            foreach ($attempts as $attempt) {
                if ($outcomes[$attempt->id] === 'PRINTED') {
                    $this->confirm($attempt);
                } else {
                    $this->reportFailure($attempt, 'Staff recorded Not Printed in Printing Completion.');
                }
            }
        });
    }

    public function confirm(IdPrintAttempt $attempt): void
    {
        DB::transaction(function () use ($attempt): void {
            $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($attempt->senior_citizen_id);
            Gate::authorize('manageId', $senior);
            abort_unless(in_array($senior->status->value, ['VERIFIED', 'ARCHIVED'], true) && $senior->verified_at && ! $senior->died_on && ! $senior->death_declared_at, 422, 'Only eligible records can have printing confirmed.');
            $attempt = IdPrintAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            abort_unless($attempt->status === 'GENERATED', 409, 'This print attempt has already been resolved.');
            abort_unless($attempt->generated_by === auth()->id() || $attempt->legacy_audit_id, 403);
            $attempt->forceFill(['status' => 'CONFIRMED', 'confirmed_by' => auth()->id(), 'confirmed_at' => now()])->save();
            if ($attempt->reprint_request_id) {
                $approval = $attempt->reprintRequest()->lockForUpdate()->firstOrFail();
                abort_unless($approval->status === 'APPROVED', 409);
                $approval->forceFill(['status' => 'CONSUMED', 'consumed_at' => now()])->save();
            }
            BenefitAudit::record('senior_citizen.id_print_confirmed', $senior, ['attempt_id' => $attempt->id, 'purpose' => $attempt->purpose]);
        });
    }

    public function reportFailure(IdPrintAttempt $attempt, string $reason): void
    {
        Validator::make(['failureReason' => trim($reason)], ['failureReason' => ['required', 'string', 'max:2000']])->validate();
        DB::transaction(function () use ($attempt, $reason): void {
            $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($attempt->senior_citizen_id);
            Gate::authorize('manageId', $senior);
            $attempt = IdPrintAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            abort_unless($attempt->status === 'GENERATED', 409, 'This print attempt has already been resolved.');
            abort_unless($attempt->generated_by === auth()->id() || $attempt->legacy_audit_id, 403);
            $attempt->forceFill(['status' => 'FAILED', 'failed_by' => auth()->id(), 'failed_at' => now(), 'failure_reason' => trim($reason)])->save();
            BenefitAudit::record('senior_citizen.id_print_failed', $senior, ['attempt_id' => $attempt->id, 'reason' => trim($reason)]);
        });
    }

    public function synchronizeLegacy(SeniorCitizen $senior): void
    {
        Gate::authorize('manageId', $senior);
        DB::transaction(function () use ($senior): void {
            $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($senior->id);
            if ($senior->idPrintAttempts()->exists()) {
                return;
            }
            $audit = $senior->idPrints()->latest('id')->first();
            if ($audit) {
                $attempt = new IdPrintAttempt;
                $attempt->forceFill(['senior_citizen_id' => $senior->id, 'legacy_audit_id' => $audit->id,
                    'purpose' => 'FIRST_PRINT', 'status' => 'GENERATED', 'generated_by' => $audit->user_id,
                    'generated_at' => $audit->created_at])->save();
            }
        });
    }
}
