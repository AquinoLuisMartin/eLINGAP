<?php

namespace App\Livewire\SeniorCitizens;

use App\Models\SeniorCitizen;
use App\Services\Benefits\SeniorEligibilityRecord;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RequirementsReview extends Component
{
    #[Locked]
    public int $seniorId;

    public bool $documentsChecked = false;

    public function mount(int $seniorId): void
    {
        $this->seniorId = $seniorId;
    }

    public function boot(): void
    {
        Gate::authorize('viewAny', SeniorCitizen::class);
    }

    public function verify(SeniorEligibilityRecord $records): void
    {
        $senior = SeniorCitizen::findOrFail($this->seniorId);
        Gate::authorize('verify', $senior);
        $this->validate(['documentsChecked' => ['accepted']]);
        $records->verify($senior, $this->documentsChecked);
        $this->reset('documentsChecked');
        $this->dispatch('registration-verified');
    }

    public function render()
    {
        return view('livewire.senior-citizens.requirements-review', [
            'senior' => SeniorCitizen::findOrFail($this->seniorId),
        ]);
    }
}
