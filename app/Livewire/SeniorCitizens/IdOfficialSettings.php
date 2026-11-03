<?php

namespace App\Livewire\SeniorCitizens;

use App\Models\SeniorCitizen;
use App\Services\SeniorCitizens\IdAssets;
use App\Services\SeniorCitizens\IdOfficials;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class IdOfficialSettings extends Component
{
    use WithFileUploads;

    public string $oscaHeadName = '';

    public string $mayorName = '';

    public $seal;

    public array $remove = [];

    public string $notice = '';

    #[Locked]
    public bool $embedded = false;

    public function boot(): void
    {
        Gate::authorize('configureId', SeniorCitizen::class);
    }

    public function mount(IdOfficials $officials, bool $embedded = false): void
    {
        $this->embedded = $embedded;
        $values = $officials->values();
        $this->oscaHeadName = $values['osca_head_name'] ?? '';
        $this->mayorName = $values['municipal_mayor_name'] ?? '';
    }

    public function save(IdOfficials $officials): void
    {
        $this->validate([
            'oscaHeadName' => ['required', 'string', 'max:150'], 'mayorName' => ['required', 'string', 'max:150'],
            'seal' => ['nullable', 'image', 'mimes:jpeg,png', 'max:2048'],
            'remove' => ['array'], 'remove.*' => ['in:municipal_seal'],
        ]);
        $officials->save(['osca_head_name' => trim($this->oscaHeadName), 'municipal_mayor_name' => trim($this->mayorName)], [
            'municipal_seal' => $this->seal,
        ], $this->remove);
        $this->reset('seal', 'remove');
        $this->notice = 'Officials and card assets saved. Future prints will use these settings.';
        $this->dispatch('id-officials-updated');
    }

    public function render(IdOfficials $officials, IdAssets $assets)
    {
        $values = $officials->values();

        $images = ['municipal_seal' => $assets->municipalSealDataUri($values['municipal_seal'] ?? null)];

        return view('livewire.senior-citizens.id-official-settings', [
            'images' => $images, 'hasCustomSeal' => ! empty($values['municipal_seal']),
        ]);
    }
}
