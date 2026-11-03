<?php

namespace App\Livewire\SeniorCitizens;

use App\Models\SeniorCitizen;
use App\Services\SeniorCitizens\RegistrySearch;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(as: 'barangay_id', except: '')]
    public string $barangayId = '';

    #[Url(except: '')]
    public string $age = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(as: 'program_id', except: '')]
    public string $programId = '';

    #[Url(except: '')]
    public string $benefit = '';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    #[Url(as: 'search_by', except: 'all')]
    public string $searchBy = 'all';

    #[Url(except: 'id')]
    public string $sort = 'id';

    protected ?array $initialData = null;

    public function boot(): void
    {
        Gate::authorize('viewAny', SeniorCitizen::class);
    }

    public function mount(?array $initialData = null): void
    {
        $this->initialData = $initialData;
        $this->search = mb_strtoupper($this->search, 'UTF-8');
    }

    public function updated(string $property): void
    {
        if ($property === 'search') {
            $this->search = mb_strtoupper($this->search, 'UTF-8');
        }

        if (in_array($property, ['search', 'barangayId', 'age', 'status', 'programId', 'benefit', 'direction', 'searchBy', 'sort'])) {
            $this->resetPage();
            Validator::make($this->filters(), RegistrySearch::rules())->validate();
        }
    }

    public function sortByName(string $direction): void
    {
        Validator::make(['direction' => $direction], ['direction' => ['nullable', 'in:asc,desc']])->validate();

        $this->resetValidation('direction');
        $this->sort = $direction === '' ? 'id' : 'last_name';
        $this->direction = $direction === '' ? 'desc' : $direction;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'barangayId', 'age', 'status', 'programId', 'benefit', 'direction', 'searchBy', 'sort');
        $this->resetValidation();
        $this->resetPage();
    }

    protected function filters(): array
    {
        return [
            'search' => $this->search, 'barangay_id' => $this->barangayId,
            'age' => $this->age, 'status' => $this->status, 'program_id' => $this->programId,
            'benefit' => $this->benefit, 'direction' => $this->direction,
            'search_by' => $this->searchBy, 'sort' => $this->sort,
        ];
    }

    public function render(RegistrySearch $search)
    {
        $filters = Validator::make($this->filters(), RegistrySearch::rules())->valid();

        return view('livewire.senior-citizens.index', [
            ...($this->initialData ?? $search->data($filters, $this->getPage())),
            'navigationFilters' => [...array_filter($filters, fn ($value) => filled($value)), 'page' => $this->getPage()],
        ]);
    }
}
