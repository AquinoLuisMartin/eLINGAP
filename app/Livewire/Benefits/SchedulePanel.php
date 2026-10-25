<?php

namespace App\Livewire\Benefits;

use App\Models\Barangay;
use App\Models\BenefitEntitlement;
use App\Models\BenefitSchedule;
use App\Services\Benefits\BenefitPayments;
use App\Services\Benefits\BenefitScheduling;
use App\Services\Benefits\BenefitSms;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class SchedulePanel extends Component
{
    use WithPagination;

    #[Locked]
    public int $scheduleId;

    public string $reviewNote = '';

    public string $notice = '';

    public string $amount = '';

    public string $funds = '0';

    public string $claimant = 'SELF';

    public string $externalReference = '';

    public bool $requirementsChecked = false;

    public bool $groceriesReceived = false;

    public bool $paymentConfirmed = false;

    public string $message = '';

    public string $smsBarangay = '';

    public bool $smsApproved = false;

    #[Locked]
    public array $preview = [];

    public function boot(): void
    {
        Gate::authorize('viewAny', BenefitSchedule::class);
    }

    public function mount(int $scheduleId): void
    {
        $this->scheduleId = $scheduleId;
        $schedule = $this->schedule();
        $this->amount = $schedule->amount ?? '';
        $this->funds = $schedule->available_funds;
        $this->message = app(BenefitSms::class)->template($schedule);
    }

    private function schedule(): BenefitSchedule
    {
        return BenefitSchedule::query()->with('program')->findOrFail($this->scheduleId);
    }

    private function entry(int $id): BenefitEntitlement
    {
        $entry = BenefitEntitlement::query()->where('benefit_schedule_id', $this->scheduleId)->find($id);
        abort_unless($entry, 404);

        return $entry;
    }

    public function review(int $id, bool $include, BenefitScheduling $service): void
    {
        $this->validate(['reviewNote' => ['nullable', 'string', 'max:1000']]);
        $service->review($this->entry($id), $include, $this->reviewNote);
        $this->notice = $include ? 'Recipient checked and included.' : 'Benefit remains pending for a later schedule.';
    }

    public function approve(BenefitScheduling $service): void
    {
        $service->approve($this->schedule());
        $this->notice = 'Staff review completed. The schedule is approved for release.';
    }

    public function regenerate(BenefitScheduling $service): void
    {
        $service->prepare($this->schedule());
        $this->notice = 'Recipient preparation requested. Review the list once preparation finishes.';
    }

    public function fund(BenefitScheduling $service): void
    {
        $this->validate(['amount' => ['nullable', 'numeric', 'min:0.01', 'max:9999999999.99'], 'funds' => ['required', 'numeric', 'min:0', 'max:999999999999.99']]);
        $service->fund($this->schedule(), $this->amount ?: null, $this->funds);
        $this->notice = 'Funding recorded. Previously recorded entitlement amounts were preserved.';
    }

    public function close(BenefitScheduling $service): void
    {
        $service->close($this->schedule());
        $this->notice = 'Schedule closed. Uncollected funds are marked unclaimed.';
    }

    public function cancel(BenefitScheduling $service): void
    {
        $service->cancel($this->schedule());
        $this->notice = 'Draft cancelled. Entitlements remain pending.';
    }

    public function claim(int $id, BenefitPayments $service): void
    {
        $this->validate([
            'claimant' => ['required', 'in:SELF,REPRESENTATIVE'], 'externalReference' => ['nullable', 'string', 'max:180'],
            'requirementsChecked' => ['boolean'], 'groceriesReceived' => ['boolean'], 'paymentConfirmed' => ['accepted'],
        ]);
        $payment = $service->claim($this->entry($id), ['claimant_type' => $this->claimant, 'external_reference' => $this->externalReference, 'requirements_checked' => $this->requirementsChecked, 'groceries_received' => $this->groceriesReceived]);
        $this->reset('paymentConfirmed', 'requirementsChecked', 'groceriesReceived', 'externalReference');
        $this->notice = 'Claimed. Payment reference: '.$payment->reference_number;
    }

    public function previewSms(BenefitSms $service): void
    {
        $this->validate(['message' => ['required', 'string', 'max:1600'], 'smsBarangay' => ['nullable', 'integer', 'exists:barangays,id']]);
        $this->preview = $service->preview($this->schedule(), $this->message, $this->smsBarangay ? (int) $this->smsBarangay : null);
        $this->smsApproved = false;
    }

    public function sendSms(BenefitSms $service): void
    {
        $this->validate(['smsApproved' => ['accepted'], 'message' => ['required', 'string', 'max:1600'], 'smsBarangay' => ['nullable', 'integer', 'exists:barangays,id']]);
        abort_unless(isset($this->preview['hash']), 422, 'Preview the message first.');
        $blast = $service->send($this->schedule(), $this->message, $this->smsBarangay ? (int) $this->smsBarangay : null, $this->preview['hash']);
        $this->reset('preview', 'smsApproved');
        $this->notice = $blast->recipient_count.' payout SMS messages queued. Failed sends remain available for staff retry.';
    }

    public function render(): View
    {
        $schedule = $this->schedule();
        $entries = $schedule->entitlements()->select(['id', 'benefit_schedule_id', 'senior_citizen_id', 'period_key', 'amount', 'status', 'reviewed_at'])
            ->with([
                'seniorCitizen:id,barangay_id,osca_id_number,first_name,middle_name,last_name,name_suffix,status',
                'seniorCitizen.barangay:id,name', 'payments:id,benefit_entitlement_id,reference_number,reversed_at',
            ])->orderBy('id')->paginate(15, pageName: 'recipientsPage');

        return view('livewire.benefits.schedule-panel', [
            'schedule' => $schedule,
            'entries' => $entries,
            'recipientTotal' => $entries->total(),
            'requiredFunds' => $schedule->entitlements()->sum('amount'),
            'barangays' => Barangay::query()->when($schedule->barangay_ids, fn ($query) => $query->whereIn('id', $schedule->barangay_ids))->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
