<?php

namespace App\Livewire\Registrations;

use App\Models\Barangay;
use App\Models\SeniorCitizen;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public bool $applicationOpen = false;

    public string $search = '';

    public string $stage = '';

    public string $barangayId = '';

    public function boot(): void
    {
        Gate::authorize('viewAny', SeniorCitizen::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'stage', 'barangayId'])) {
            $this->resetPage();
        }
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
        $query = SeniorCitizen::query()->with('barangay:id,name')
            ->when(trim($this->search) !== '', fn ($q) => $q->searchable(mb_substr(trim($this->search), 0, 100)))
            ->when($this->barangayId !== '', fn ($q) => $q->where('barangay_id', (int) $this->barangayId));
        match ($this->stage) {
            'PENDING' => $query->where('status', 'PENDING'),
            'VERIFIED' => $query->where('status', 'VERIFIED')->whereNull('id_generated_at'),
            'ISSUED' => $query->where('status', 'VERIFIED')->whereNotNull('id_generated_at'),
            default => null,
        };

        return view('livewire.registrations.index', [
            'registrations' => $query->latest('id')->paginate(15),
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
            'counts' => SeniorCitizen::query()->selectRaw("sum(case when status = 'PENDING' then 1 else 0 end) as pending, sum(case when status = 'VERIFIED' and id_generated_at is null then 1 else 0 end) as verified, sum(case when status = 'VERIFIED' and id_generated_at is not null then 1 else 0 end) as issued")->toBase()->first(),
        ]);
    }
}
