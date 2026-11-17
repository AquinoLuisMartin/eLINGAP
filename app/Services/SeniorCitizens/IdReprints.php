<?php

namespace App\Services\SeniorCitizens;

use App\Models\IdReprintRequest;
use App\Models\SeniorCitizen;
use App\Services\Benefits\BenefitAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IdReprints
{
    public function request(SeniorCitizen $senior, string $reason, string $details): IdReprintRequest
    {
        Gate::authorize('manageId', $senior);
        $data = Validator::make(['reason' => $reason, 'details' => trim($details)], [
            'reason' => ['required', Rule::in(array_keys(IdReprintRequest::REASONS))],
            'details' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        return DB::transaction(function () use ($senior, $data): IdReprintRequest {
            $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($senior->id);
            abort_unless(in_array($senior->status->value, ['VERIFIED', 'ARCHIVED'], true) && $senior->verified_at && ! $senior->died_on && ! $senior->death_declared_at, 422, 'Only eligible records can request a reprint.');
            abort_unless($senior->idPrintAttempts()->where('status', 'CONFIRMED')->exists(), 422, 'Confirm the original physical print before requesting a reprint.');
            abort_if($senior->idPrintAttempts()->where('status', 'GENERATED')->exists(), 409, 'Resolve the current print attempt first.');
            if ($senior->idReprintRequests()->whereIn('status', ['PENDING', 'APPROVED'])->exists()) {
                throw ValidationException::withMessages(['reason' => 'A pending or approved reprint request already exists for this ID.']);
            }
            $request = new IdReprintRequest;
            $request->forceFill([...$data, 'senior_citizen_id' => $senior->id, 'requested_by' => auth()->id(), 'status' => 'PENDING'])->save();
            BenefitAudit::record('senior_citizen.id_reprint_requested', $senior, ['request_id' => $request->id, 'reason' => $request->reason]);

            return $request;
        });
    }

    public function review(IdReprintRequest $request, bool $approve, string $reviewReason = ''): void
    {
        Gate::authorize('approveIdReprints', SeniorCitizen::class);
        Validator::make(['reviewReason' => trim($reviewReason)], ['reviewReason' => [$approve ? 'nullable' : 'required', 'string', 'max:2000']])->validate();
        DB::transaction(function () use ($request, $approve, $reviewReason): void {
            $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($request->senior_citizen_id);
            $request = IdReprintRequest::query()->lockForUpdate()->findOrFail($request->id);
            abort_unless($request->status === 'PENDING', 409, 'This request has already been reviewed.');
            $request->forceFill(['status' => $approve ? 'APPROVED' : 'REJECTED', 'reviewed_by' => auth()->id(),
                'reviewed_at' => now(), 'review_reason' => trim($reviewReason) ?: null])->save();
            BenefitAudit::record($approve ? 'senior_citizen.id_reprint_approved' : 'senior_citizen.id_reprint_rejected', $senior,
                ['request_id' => $request->id, 'review_reason' => $request->review_reason]);
        });
    }
}
