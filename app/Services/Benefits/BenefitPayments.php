<?php

namespace App\Services\Benefits;

use App\Enums\BenefitStatus;
use App\Models\BenefitEntitlement;
use App\Models\BenefitPayment;
use App\Models\BenefitSchedule;
use App\Models\SeniorCitizen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BenefitPayments
{
    public function __construct(private BenefitEligibility $eligibility) {}

    public function claim(BenefitEntitlement $entitlement, array $data): BenefitPayment
    {
        Gate::authorize('manage', BenefitSchedule::class);
        validator($data, [
            'claimant_type' => ['required', 'in:SELF,REPRESENTATIVE'],
            'identity_checked' => ['accepted'],
            'authorization_checked' => ['required_if:claimant_type,REPRESENTATIVE', 'accepted_if:claimant_type,REPRESENTATIVE'],
            'groceries_received' => ['sometimes', 'boolean'],
            'requirements_checked' => ['sometimes', 'boolean'],
        ])->validate();

        return DB::transaction(function () use ($entitlement, $data) {
            $schedule = BenefitSchedule::query()->with('program')->lockForUpdate()->findOrFail($entitlement->benefit_schedule_id);
            $demo = (bool) ($schedule->rule_snapshot['local_demo'] ?? false);
            if ($demo) {
                abort_unless(app()->environment('local') && in_array(DB::connection()->getConfig('host'), ['localhost', '127.0.0.1', '::1'], true), 403, 'Demo payout schedules can only be used with a local database.');
            }
            $entitlement = BenefitEntitlement::query()->lockForUpdate()->findOrFail($entitlement->id);
            $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($entitlement->senior_citizen_id);
            abort_unless(in_array($entitlement->status, [BenefitStatus::Unpaid, BenefitStatus::Unclaimed], true) && $entitlement->benefit_schedule_id === $schedule->id, 409, 'This entitlement is not awaiting payment.');
            abort_unless($schedule->status === 'APPROVED' && $entitlement->reviewed_at && $schedule->scheduled_at->lte(now()), 422, 'Staff approval and the scheduled payout date are required.');
            abort_unless($schedule->program->status === 'ACTIVE', 422, 'The program is not active.');
            abort_unless($entitlement->amount > 0 && round((float) $schedule->entitlements()->sum('amount'), 2) <= (float) $schedule->available_funds, 422, 'Approved funding must cover the payout schedule.');
            if (isset($data['reviewed_amount'])) {
                abort_unless($data['reviewed_amount'] === $entitlement->amount && $data['reviewed_schedule_id'] === $schedule->id, 409, 'The payout details changed. Review the claim again.');
            }
            $on = isset($entitlement->rule_snapshot['claim_window_months']) ? now()->startOfDay() : $entitlement->eligibility_on;
            if ($reason = $this->eligibility->reason($senior, $entitlement->rule_snapshot, $on, $entitlement->milestone_age)) {
                throw ValidationException::withMessages(['claim' => $reason]);
            }
            if ($entitlement->payments()->whereNull('reversed_at')->exists()) {
                abort(409, 'This benefit has already been paid.');
            }
            if ($data['claimant_type'] === 'REPRESENTATIVE') {
                abort_unless($schedule->allow_representative && ($data['claimant_name'] ?? $senior->representative_name) && $senior->documents()->where('document_type', 'AUTHORIZATION')->exists(), 422, 'Representative claiming requires schedule permission and recorded authorization.');
            }
            if ($schedule->requirements && ! ($data['requirements_checked'] ?? false)) {
                throw ValidationException::withMessages(['claim' => 'Confirm the announced requirements before recording payment.']);
            }
            if (($entitlement->rule_snapshot['groceries'] ?? false) && ! ($data['groceries_received'] ?? false)) {
                throw ValidationException::withMessages(['claim' => 'Confirm receipt of groceries for this benefit.']);
            }
            $claimant = [
                'claimant_name' => $data['claimant_type'] === 'SELF' ? $senior->full_name : ($data['claimant_name'] ?? $senior->representative_name),
                'claimant_relationship' => $data['claimant_type'] === 'SELF' ? null : ($data['claimant_relationship'] ?? $senior->representative_relationship),
                'claimant_contact' => $data['claimant_type'] === 'SELF' ? $senior->contact_number : ($data['claimant_contact'] ?? $senior->representative_contact),
            ];
            validator($claimant, [
                'claimant_name' => ['required', 'string', 'max:350'],
                'claimant_relationship' => [$data['claimant_type'] === 'REPRESENTATIVE' ? 'required' : 'nullable', 'string', 'max:100'],
                'claimant_contact' => ['nullable', 'string', 'max:30'],
            ])->validate();
            if ($data['claimant_type'] === 'SELF' && array_key_exists('claimant_name', $data)) {
                abort_unless(array_intersect_key($data, $claimant) === $claimant, 409, 'The claimant details changed. Review the claim again.');
            }
            $payment = BenefitPayment::create([
                'benefit_entitlement_id' => $entitlement->id, 'benefit_schedule_id' => $schedule->id,
                'reference_number' => ($demo ? 'DEMO-LOCAL-' : 'PAY-').Str::ulid(), 'amount' => $entitlement->amount,
                'claimant_type' => $data['claimant_type'], 'external_reference' => $data['external_reference'] ?? null,
                ...$claimant,
                'identity_verified' => true, 'authorization_verified' => $data['claimant_type'] === 'REPRESENTATIVE',
                'groceries_received' => $data['groceries_received'] ?? false, 'recorded_by' => auth()->id(), 'paid_at' => now(),
            ]);
            $entitlement->update(['status' => BenefitStatus::Claimed, 'claimed_at' => now()]);
            $schedule->recipients()->where('benefit_entitlement_id', $entitlement->id)->update(['outcome' => 'CLAIMED']);
            BenefitAudit::record($demo ? 'local_demo.benefit_claimed' : 'benefit.claimed', $entitlement, ['reference' => $payment->reference_number, 'payment_id' => $payment->id, 'claimant_type' => $payment->claimant_type, 'identity_verified' => true, 'authorization_verified' => $payment->authorization_verified, 'local_demo' => $demo]);

            return $payment;
        });
    }

    public function reverse(BenefitPayment $payment, string $reason): void
    {
        Gate::authorize('reverse', BenefitPayment::class);
        validator(['reason' => $reason], ['reason' => ['required', 'string', 'min:10', 'max:1000']])->validate();
        DB::transaction(function () use ($payment, $reason): void {
            $schedule = BenefitSchedule::query()->lockForUpdate()->findOrFail($payment->benefit_schedule_id);
            $entitlement = BenefitEntitlement::query()->lockForUpdate()->findOrFail($payment->benefit_entitlement_id);
            $payment = BenefitPayment::query()->lockForUpdate()->findOrFail($payment->id);
            abort_if($payment->reversed_at || $entitlement->status !== BenefitStatus::Claimed, 409, 'Only an active claimed payment can be reversed.');
            $payment->update(['reversed_at' => now(), 'reversed_by' => auth()->id(), 'reversal_reason' => $reason]);
            $entitlement->update(['status' => $schedule->status === 'CLOSED' ? BenefitStatus::Unclaimed : BenefitStatus::Unpaid, 'claimed_at' => null]);
            $schedule->recipients()->where('benefit_entitlement_id', $entitlement->id)->update(['outcome' => 'REVERSED']);
            BenefitAudit::record('benefit.payment_reversed', $payment, ['reason' => $reason, 'original_reference' => $payment->reference_number]);
        });
    }
}
