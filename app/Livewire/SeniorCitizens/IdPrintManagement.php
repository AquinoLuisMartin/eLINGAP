<?php

namespace App\Livewire\SeniorCitizens;

use App\Models\SeniorCitizen;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class IdPrintManagement extends Component
{
    #[Locked]
    public ?int $seniorId = null;

    public function mount(?int $initialSeniorId = null): void
    {
        if ($initialSeniorId) {
            $this->open($initialSeniorId);
        }
    }

    #[On('manage-id-print')]
    public function open(int $seniorId): void
    {
        $senior = SeniorCitizen::findOrFail($seniorId);
        Gate::authorize('manageId', $senior);
        $this->seniorId = $seniorId;
    }

    public function close(): void
    {
        $this->reset('seniorId');
    }

    private function senior(): SeniorCitizen
    {
        $senior = SeniorCitizen::findOrFail($this->seniorId);
        Gate::authorize('manageId', $senior);

        return $senior;
    }

    public function render()
    {
        $senior = $this->seniorId ? $this->senior()->load(['lastConfirmedIdPrint.confirmedBy', 'latestIdReprintRequest']) : null;

        return view('livewire.senior-citizens.id-print-management', ['senior' => $senior]);
    }
}
