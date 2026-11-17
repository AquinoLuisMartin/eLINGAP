<?php

namespace App\Livewire\SeniorCitizens;

use App\Models\IdReprintRequest;
use App\Models\SeniorCitizen;
use App\Services\SeniorCitizens\IdReprints;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class RequestIdReprint extends Component
{
    #[Locked]
    public ?int $seniorId = null;

    public string $reason = '';

    public string $details = '';

    #[On('request-id-reprint')]
    public function open(int $seniorId): void
    {
        Gate::authorize('manageId', SeniorCitizen::findOrFail($seniorId));
        $this->close();
        $this->seniorId = $seniorId;
    }

    public function close(): void
    {
        $this->reset('seniorId', 'reason', 'details');
        $this->resetValidation();
    }

    public function requestReprint(IdReprints $reprints): void
    {
        $reprints->request(SeniorCitizen::findOrFail($this->seniorId), $this->reason, $this->details);
        $this->close();
        $this->dispatch('id-print-updated');
        $this->dispatch('id-reprint-requested');
    }

    public function render()
    {
        $senior = $this->seniorId ? SeniorCitizen::findOrFail($this->seniorId) : null;
        if ($senior) {
            Gate::authorize('manageId', $senior);
        }

        return view('livewire.senior-citizens.request-id-reprint', ['senior' => $senior, 'reasons' => IdReprintRequest::REASONS]);
    }
}
