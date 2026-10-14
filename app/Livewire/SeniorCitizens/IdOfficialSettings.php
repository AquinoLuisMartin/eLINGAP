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

    public $headSignature;

    public $mayorSignature;

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
            'headSignature' => ['nullable', 'image', 'mimes:jpeg,png', 'max:2048'],
            'mayorSignature' => ['nullable', 'image', 'mimes:jpeg,png', 'max:2048'],
            'seal' => ['nullable', 'image', 'mimes:jpeg,png', 'max:2048'],
            'remove' => ['array'], 'remove.*' => ['in:osca_head_signature,municipal_mayor_signature,municipal_seal'],
        ]);
        $officials->save(['osca_head_name' => trim($this->oscaHeadName), 'municipal_mayor_name' => trim($this->mayorName)], [
            'osca_head_signature' => $this->headSignature, 'municipal_mayor_signature' => $this->mayorSignature, 'municipal_seal' => $this->seal,
        ], $this->remove);
        $this->reset('headSignature', 'mayorSignature', 'seal', 'remove');
        $this->notice = 'Officials and card assets saved. Future prints will use these settings.';
        $this->dispatch('id-officials-updated');
    }

    public function render(IdOfficials $officials, IdAssets $assets)
    {
        $values = $officials->values();

        $images = collect(['osca_head_signature', 'municipal_mayor_signature'])
            ->mapWithKeys(fn ($key) => [$key => $assets->pathDataUri($values[$key] ?? null)])->all();
        $images['municipal_seal'] = $assets->municipalSealDataUri($values['municipal_seal'] ?? null);

        return view('livewire.senior-citizens.id-official-settings', [
            'images' => $images, 'hasCustomSeal' => ! empty($values['municipal_seal']),
        ]);
    }
}
