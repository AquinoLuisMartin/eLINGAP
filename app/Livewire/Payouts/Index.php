<?php

namespace App\Livewire\Payouts;

use App\Enums\PayoutStatus;
use App\Models\Barangay;
use App\Models\Payout;
use App\Models\Program;
use App\Services\Reports\PayoutRecords;
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

    #[Url(except: '')]
    public string $status = '';

    #[Url(as: 'program_id', except: '')]
    public string $programId = '';

    #[Url(as: 'barangay_id', except: '')]
    public string $barangayId = '';

    public function boot(): void
    {
        Gate::authorize('viewAny', Payout::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'programId', 'barangayId'], true)) {
            $this->resetPage();
            $this->validate();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status', 'programId', 'barangayId');
        $this->resetValidation();
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:PENDING,UNPAID,UNCLAIMED,CLAIMED,RELEASED,FAILED,VOIDED'],
            'programId' => ['nullable', 'integer', 'exists:programs,id'],
            'barangayId' => ['nullable', 'integer', 'exists:barangays,id'],
        ];
    }

    public function render(PayoutRecords $records)
    {
        $validated = Validator::make($this->only(['search', 'status', 'programId', 'barangayId']), $this->rules())->valid();
        $filters = ['search' => $validated['search'] ?? null, 'status' => $validated['status'] ?? null, 'program_id' => $validated['programId'] ?? null, 'barangay_id' => $validated['barangayId'] ?? null];
        $query = $records->query($filters);
        $payouts = (clone $query)->orderByDesc('created_at')->orderBy('source')->orderByDesc('id')->paginate(15);
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
