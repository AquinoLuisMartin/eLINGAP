<?php

namespace App\Livewire\SeniorCitizens;

use App\Enums\SeniorCitizenStatus;
use App\Models\SeniorCitizen;
use App\Models\SeniorCitizenIdCredential;
use App\Services\Benefits\SeniorEligibilityRecord;
use App\Services\SeniorCitizens\IdCardLayout;
use App\Services\SeniorCitizens\IdCredential;
use App\Services\SeniorCitizens\IdPrinting;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class IdManager extends Component
{
    #[Locked]
    public int $seniorId;

    #[Locked]
    public int $step = 1;

    public bool $reviewed = false;

    public string $notice = '';

    public bool $officialsOpen = false;

    #[Locked]
    public bool $existingId = false;

    #[On('registration-verified')]
    public function registrationVerified(): void
    {
        $this->notice = 'Requirements verified. Continue with ID preparation.';
    }

    #[On('id-officials-updated')]
    public function officialsUpdated(): void
    {
        $this->officialsOpen = false;
        $this->step = min(2, $this->step);
        $this->reviewed = false;
        $this->notice = 'Official settings saved. Review the updated card before printing.';
    }

    public function mount(IdPrinting $printing, int $seniorId): void
    {
        $this->seniorId = $seniorId;
        $this->existingId = (bool) $this->senior()->id_generated_at;
        $printing->synchronizeLegacy($this->senior());
    }

    #[On('id-print-updated')]
    public function refreshPrinting(): void {}

    #[On('id-reprint-requested')]
    public function reprintRequested(): void
    {
        $this->notice = 'Reprint request submitted. Printing remains locked until approval.';
    }

    public function boot(): void
    {
        if (isset($this->seniorId)) {
            Gate::authorize('manageId', SeniorCitizen::findOrFail($this->seniorId));
        }
    }

    public function goToStep(int $step): void
    {
        abort_unless(in_array($step, [1, 2, 3], true), 422);
        $this->senior();
        $this->step = $step;
        $this->reviewed = false;
        $this->resetValidation();
    }

    public function next(): void
    {
        $this->goToStep(2);
    }

    public function back(): void
    {
        $this->goToStep(max(1, $this->step - 1));
    }

    public function issue(SeniorEligibilityRecord $records): void
    {
        $this->validate(['reviewed' => ['accepted']]);
        abort_unless($this->step === 2, 422);
        $records->issueId($this->senior());
        $this->step = 3;
        $this->notice = '';
        $this->dispatch('id-ready-to-print', message: 'The ID has been issued. Open the print-ready PDF, then record the physical printing result.');
    }

    public function prepareExisting(IdCredential $credentials): void
    {
        $this->validate(['reviewed' => ['accepted']]);
        abort_unless($this->step === 2, 422);
        $senior = $this->senior();
        $credentials->createForIssued($senior);
        $this->step = 3;
        $this->notice = '';
        $this->dispatch('id-ready-to-print', message: 'The existing ID number and original issuance date are unchanged. Open the print-ready PDF, then record the physical printing result.');
    }

    private function senior(): SeniorCitizen
    {
        $senior = SeniorCitizen::findOrFail($this->seniorId);
        Gate::authorize('manageId', $senior);

        return $senior;
    }

    public function render(IdCardLayout $layout)
    {
        $senior = $this->senior()->load(['latestIdPrintAttempt', 'lastConfirmedIdPrint', 'latestIdReprintRequest']);

        return view('livewire.senior-citizens.id-manager', [
            'senior' => $senior,
            'printingLocked' => ! $senior->canGenerateIdPrint(),
            'documents' => $senior->documents()->where('document_type', '!=', 'PHOTO')->latest('id')->get(['id', 'document_type']),
            'card' => ! in_array($senior->status, [SeniorCitizenStatus::Deceased, SeniorCitizenStatus::Transferred], true) && ! $senior->died_on && ! $senior->death_declared_at ? $layout->data($senior, true) : null,
            'credentialExists' => SeniorCitizenIdCredential::query()->where('senior_citizen_id', $senior->id)->exists(),
        ]);
    }
}
