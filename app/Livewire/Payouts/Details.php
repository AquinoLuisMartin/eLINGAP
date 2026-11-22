<?php

namespace App\Livewire\Payouts;

use App\Enums\BenefitStatus;
use App\Models\BenefitEntitlement;
use App\Models\BenefitSchedule;
use App\Models\Payout;
use App\Services\Benefits\BenefitEligibility;
use App\Services\Benefits\BenefitPayments;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Details extends Component
{
    #[Locked]
    public ?int $payoutId = null;

    #[Locked]
    public string $source = 'benefit';

    #[Locked]
    public ?array $review = null;

    #[Locked]
    public bool $claimFormOpen = false;

    public array $form = ['claimant_type' => 'SELF', 'claimant_name' => '', 'claimant_relationship' => '', 'claimant_contact' => '', 'identity_checked' => false, 'authorization_checked' => false, 'requirements_checked' => false, 'groceries_received' => false];

    #[Locked]
    public string $message = '';

    #[Locked]
    public bool $succeeded = false;

    public function boot(): void
    {
        Gate::authorize('viewAny', Payout::class);
    }

    #[On('view-payout')]
    public function open(string $source, int $payoutId): void
    {
        abort_unless(in_array($source, ['benefit', 'legacy'], true), 404);
        $this->reset('review', 'form', 'message', 'succeeded', 'claimFormOpen');
        $this->resetValidation();
        $this->source = $source;
        $this->payoutId = $payoutId;
        $record = $this->record();
        $this->form['claimant_name'] = $record->seniorCitizen->representative_name ?? '';
        $this->form['claimant_relationship'] = $record->seniorCitizen->representative_relationship ?? '';
        $this->form['claimant_contact'] = $record->seniorCitizen->representative_contact ?? '';
    }

    public function close(): void
    {
        $this->reset('payoutId', 'review', 'form', 'claimFormOpen');
        $this->resetValidation();
    }

    public function dismissMessage(): void
    {
        $this->message = '';
    }

    public function cancelReview(): void
    {
        $this->review = null;
    }

    public function startClaim(): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        $entry = $this->record();
        if (! $entry instanceof BenefitEntitlement || ! $this->canClaim($entry)) {
            $this->showError('This payout is not approved and funded for release.');

            return;
        }
        $this->resetValidation();
        $this->message = '';
        $this->claimFormOpen = true;
    }

    public function cancelClaim(): void
    {
        $this->reset('review', 'claimFormOpen');
        $this->form['identity_checked'] = false;
        $this->form['authorization_checked'] = false;
        $this->resetValidation();
    }

    public function updatedForm(): void
    {
        $this->review = null;
    }

    public function reviewClaim(): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        $this->message = '';
        $this->review = null;
        $validated = $this->validate([
            'form.claimant_type' => ['required', 'in:SELF,REPRESENTATIVE'],
            'form.identity_checked' => ['accepted'],
            'form.authorization_checked' => ['required_if:form.claimant_type,REPRESENTATIVE', 'accepted_if:form.claimant_type,REPRESENTATIVE'],
            'form.claimant_name' => ['required_if:form.claimant_type,REPRESENTATIVE', 'nullable', 'string', 'max:350'],
            'form.claimant_relationship' => ['required_if:form.claimant_type,REPRESENTATIVE', 'nullable', 'string', 'max:100'],
            'form.claimant_contact' => ['nullable', 'string', 'max:30'],
            'form.requirements_checked' => ['required', 'boolean'],
            'form.groceries_received' => ['required', 'boolean'],
        ]);
        $entry = $this->record();
        if (! $entry instanceof BenefitEntitlement || ! $this->canClaim($entry)) {
            $this->showError('This payout requires approval, available funding and a due payout date, and must not already be claimed.');

            return;
        }
        $senior = $entry->seniorCitizen;
        $representative = $this->form['claimant_type'] === 'REPRESENTATIVE';
        $this->review = [
            ...$validated['form'],
            'reviewed_amount' => $entry->amount,
            'reviewed_schedule_id' => $entry->benefit_schedule_id,
            'claimant_name' => $representative ? $this->form['claimant_name'] : $senior->full_name,
            'claimant_relationship' => $representative ? $this->form['claimant_relationship'] : null,
            'claimant_contact' => $representative ? $this->form['claimant_contact'] : $senior->contact_number,
        ];
    }

    public function confirmClaim(BenefitPayments $payments): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        if ($this->review === null || $this->source !== 'benefit' || ! $this->payoutId) {
            $this->showError('Review and confirm the payout details before recording a claim.');

            return;
        }
        try {
            $payments->claim(BenefitEntitlement::findOrFail($this->payoutId), $this->review);
            $this->close();
            $this->succeeded = true;
            $this->message = 'Claim recorded successfully.';
            $this->dispatch('payout-claimed');
        } catch (ValidationException $exception) {
            $this->showError(collect($exception->errors())->flatten()->first());
        } catch (HttpExceptionInterface $exception) {
            $this->showError($exception->getMessage() ?: 'This payout can no longer be claimed. Refresh and review its details.');
        } catch (Throwable $exception) {
            report($exception);
            $this->showError('The claim could not be saved. No payment was recorded. Please try again.');
        }
    }

    private function showError(string $message): void
    {
        $this->review = null;
        $this->succeeded = false;
        $this->message = $message;
    }

    private function record(): BenefitEntitlement|Payout|null
    {
        if (! $this->payoutId) {
            return null;
        }
        if ($this->source === 'legacy') {
            $record = Payout::with(['seniorCitizen.barangay', 'schedule.program', 'releasedBy'])->findOrFail($this->payoutId);
            Gate::authorize('view', $record);

            return $record;
        }
        Gate::authorize('viewAny', BenefitSchedule::class);

        return BenefitEntitlement::with(['seniorCitizen.barangay', 'program', 'schedule', 'payments' => fn ($query) => $query->latest('paid_at')->latest('id'), 'payments.recordedBy'])->findOrFail($this->payoutId);
    }

    private function canClaim(BenefitEntitlement $entry): bool
    {
        return Gate::allows('manage', BenefitSchedule::class)
            && in_array($entry->status, [BenefitStatus::Unpaid, BenefitStatus::Unclaimed], true)
            && $entry->reviewed_at !== null
            && $entry->amount > 0
            && $entry->schedule?->status === 'APPROVED'
            && $entry->schedule->scheduled_at->lte(now())
            && $entry->program->status === 'ACTIVE'
            && $entry->payments->whereNull('reversed_at')->isEmpty()
            && round((float) $entry->schedule->entitlements()->sum('amount'), 2) <= (float) $entry->schedule->available_funds
            && app(BenefitEligibility::class)->reason($entry->seniorCitizen, $entry->rule_snapshot, isset($entry->rule_snapshot['claim_window_months']) ? now()->startOfDay() : $entry->eligibility_on, $entry->milestone_age) === null;
    }

    public function render()
    {
        $record = $this->review === null ? $this->record() : null;

        return view('livewire.payouts.details', [
            'record' => $record,
            'isBenefit' => $record instanceof BenefitEntitlement,
            'canClaim' => $record instanceof BenefitEntitlement && $this->canClaim($record),
        ]);
    }
}
