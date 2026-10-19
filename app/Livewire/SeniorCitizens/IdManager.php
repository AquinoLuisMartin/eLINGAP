<?php

namespace App\Livewire\SeniorCitizens;

use App\Enums\SeniorCitizenStatus;
use App\Models\AuditLog;
use App\Models\SeniorCitizen;
use App\Models\SeniorCitizenIdCredential;
use App\Services\Benefits\SeniorEligibilityRecord;
use App\Services\SeniorCitizens\IdCardLayout;
use App\Services\SeniorCitizens\IdCredential;
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

    public function mount(int $seniorId): void
    {
        $this->seniorId = $seniorId;
        $this->existingId = (bool) $this->senior()->id_generated_at;
    }

    public function boot(): void
    {
        if (isset($this->seniorId)) {
            Gate::authorize('manageId', SeniorCitizen::findOrFail($this->seniorId));
        }
    }

    public function next(): void
    {
        $this->senior();
        $this->step = 2;
        $this->reviewed = false;
        $this->resetValidation();
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
        $this->reviewed = false;
        $this->resetValidation();
    }

    public function issue(SeniorEligibilityRecord $records): void
    {
        $this->validate(['reviewed' => ['accepted']]);
        abort_unless($this->step === 2, 422);
        $records->issueId($this->senior());
        $this->step = 3;
        $this->notice = 'ID issued. The OSCA number, original issuance date and QR are now permanent.';
    }

    public function prepareExisting(IdCredential $credentials): void
    {
        $this->validate(['reviewed' => ['accepted']]);
        abort_unless($this->step === 2, 422);
        $senior = $this->senior();
        $credentials->createForIssued($senior);
        $this->step = 3;
        $this->notice = 'Ready to print. The existing ID number and original issuance date were preserved.';
    }

    private function senior(): SeniorCitizen
    {
        $senior = SeniorCitizen::findOrFail($this->seniorId);
        Gate::authorize('manageId', $senior);

        return $senior;
    }

    public function render(IdCardLayout $layout)
    {
        $senior = $this->senior();

        return view('livewire.senior-citizens.id-manager', [
            'senior' => $senior,
            'documents' => $senior->documents()->where('document_type', '!=', 'PHOTO')->latest('id')->get(['id', 'document_type']),
            'card' => ! in_array($senior->status, [SeniorCitizenStatus::Deceased, SeniorCitizenStatus::Transferred], true) && ! $senior->died_on && ! $senior->death_declared_at ? $layout->data($senior, true) : null,
            'credentialExists' => SeniorCitizenIdCredential::query()->where('senior_citizen_id', $senior->id)->exists(),
            'history' => AuditLog::query()->where('auditable_type', SeniorCitizen::class)->where('auditable_id', $senior->id)
                ->whereIn('action', ['senior_citizen.id_generated', 'senior_citizen.qr_enabled', 'senior_citizen.id_print_generated', 'senior_citizen.id_asset_updated', 'senior_citizen.wet_signature_selected'])
                ->with('user')->latest('id')->limit(20)->get(),
        ]);
    }
}
