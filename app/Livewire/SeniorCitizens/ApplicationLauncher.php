<?php

namespace App\Livewire\SeniorCitizens;

use App\Models\SeniorCitizen;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class ApplicationLauncher extends Component
{
    public bool $applicationOpen = false;

    public function boot(): void
    {
        Gate::authorize('create', SeniorCitizen::class);
    }

    public function apply(): void
    {
        Gate::authorize('create', SeniorCitizen::class);
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
