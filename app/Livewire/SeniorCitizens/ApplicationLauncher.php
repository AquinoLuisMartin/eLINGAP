<?php

namespace App\Livewire\SeniorCitizens;

use App\Models\SeniorCitizen;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class ApplicationLauncher extends Component
{
    public bool $applicationOpen = false;

    #[Locked]
    public int $applicationSession = 0;

    public function boot(): void
    {
        Gate::authorize('create', SeniorCitizen::class);
    }

    public function apply(): void
    {
        Gate::authorize('create', SeniorCitizen::class);
        if ($this->applicationOpen) {
            return;
        }
        $this->applicationSession++;
        session()->forget('status');
        $this->applicationOpen = true;
    }

    #[On('registration-cancelled')]
    public function closeApplication(): void
    {
        $this->applicationOpen = false;
    }

    public function render()
    {
        return view('livewire.senior-citizens.application-launcher');
    }
}
