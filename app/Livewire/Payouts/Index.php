<?php

namespace App\Livewire\Payouts;

use App\Enums\PayoutStatus;
use App\Models\Barangay;
use App\Models\Payout;
use App\Models\Program;
use App\Services\Reports\PayoutRecords;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(as: 'program_id', except: '')]
    public string $programId = '';

    #[Url(as: 'barangay_id', except: '')]
    public string $barangayId = '';

    #[Url(except: 'name_asc')]
    public string $sort = 'name_asc';

    public function boot(): void
    {
        Gate::authorize('viewAny', Payout::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'programId', 'barangayId', 'sort'], true)) {
            $this->resetPage();
            $this->validate();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status', 'programId', 'barangayId', 'sort');
        $this->resetValidation();
        $this->resetPage();
    }

    #[On('payout-claimed')]
    public function refreshPayouts(): void {}

    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:PENDING,UNPAID,UNCLAIMED,CLAIMED,RELEASED,FAILED,VOIDED'],
            'programId' => ['nullable', 'integer', 'exists:programs,id'],
            'barangayId' => ['nullable', 'integer', 'exists:barangays,id'],
            'sort' => ['required', Rule::in(array_keys(PayoutRecords::SORTS))],
        ];
    }

    public function render(PayoutRecords $records)
    {
        $validated = Validator::make($this->only(['search', 'status', 'programId', 'barangayId', 'sort']), $this->rules())->valid();
        $filters = ['search' => $validated['search'] ?? null, 'status' => $validated['status'] ?? null, 'program_id' => $validated['programId'] ?? null, 'barangay_id' => $validated['barangayId'] ?? null];
        $query = $records->query($filters);
        $payouts = $records->ordered(clone $query, $validated['sort'] ?? 'name_asc')->paginate(15);
        $payouts->setCollection($records->loadDetails($payouts->getCollection()));

        return view('livewire.payouts.index', [
            'payouts' => $payouts,
            'counts' => (clone $query)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'proxyCount' => (clone $query)->whereIn('status', ['CLAIMED', PayoutStatus::Released->value])->whereIn('claimant_type', ['PROXY', 'REPRESENTATIVE'])->count(),
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
