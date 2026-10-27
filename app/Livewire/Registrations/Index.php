<?php

namespace App\Livewire\Registrations;

use App\Models\Barangay;
use App\Models\SeniorCitizen;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(as: 'search_by', except: 'all')]
    public string $searchBy = 'all';

    #[Url(except: '')]
    public string $stage = '';

    #[Url(as: 'barangay_id', except: '')]
    public string $barangayId = '';

    public function boot(): void
    {
        Gate::authorize('viewAny', SeniorCitizen::class);
    }

    public function updated(string $property): void
    {
        if ($property === 'searchBy') {
            $this->validateOnly('searchBy', ['searchBy' => ['in:all,name,osca_id']]);
        }
        if (in_array($property, ['search', 'searchBy', 'stage', 'barangayId'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = SeniorCitizen::query()->where('status', '!=', 'PENDING')->with('barangay:id,name')
            ->when(trim($this->search) !== '', fn ($q) => $q->searchable(mb_substr(trim($this->search), 0, 100), $this->searchBy))
            ->when($this->barangayId !== '', fn ($q) => $q->where('barangay_id', (int) $this->barangayId));
        match ($this->stage) {
            'INACTIVE' => $query->whereIn('status', ['ARCHIVED', 'DECEASED', 'TRANSFERRED']),
            'VERIFIED' => $query->where('status', 'VERIFIED')->whereNull('id_generated_at'),
            'ISSUED' => $query->where('status', 'VERIFIED')->whereNotNull('id_generated_at'),
            default => null,
        };

        return view('livewire.registrations.index', [
            'registrations' => $query->select(['id', 'barangay_id', 'osca_id_number', 'first_name', 'middle_name', 'last_name', 'name_suffix', 'applicant_type', 'created_at', 'status', 'id_generated_at'])->latest('id')->paginate(15),
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
            'counts' => SeniorCitizen::query()->selectRaw("sum(case when status in ('ARCHIVED', 'DECEASED', 'TRANSFERRED') then 1 else 0 end) as inactive, sum(case when status = 'VERIFIED' and id_generated_at is null then 1 else 0 end) as verified, sum(case when status = 'VERIFIED' and id_generated_at is not null then 1 else 0 end) as issued")->toBase()->first(),
        ]);
    }
}
