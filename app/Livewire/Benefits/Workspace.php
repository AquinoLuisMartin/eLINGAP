<?php

namespace App\Livewire\Benefits;

use App\Models\Barangay;
use App\Models\BenefitSchedule;
use App\Models\Program;
use App\Services\Benefits\BenefitScheduling;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Workspace extends Component
{
    use WithPagination;

    #[Locked]
    public ?int $selectedSchedule = null;

    public array $form = ['program_id' => '', 'period_key' => '', 'eligibility_on' => '', 'scheduled_at' => '', 'location' => '', 'batch_name' => '', 'barangay_ids' => [], 'requirements' => '', 'allow_representative' => false, 'amount' => '', 'available_funds' => '0'];

    public function boot(): void
    {
        Gate::authorize('viewAny', BenefitSchedule::class);
    }

    public function mount(): void
    {
        $this->form['eligibility_on'] = today()->toDateString();
        $this->form['scheduled_at'] = now()->format('Y-m-d\TH:i');
        $this->form['period_key'] = (string) now()->year;
    }

    public function updatedFormProgramId(): void
    {
        $program = Program::find($this->form['program_id']);
        $this->form['amount'] = $program?->default_amount ?? '';
        $this->form['period_key'] = match ($program?->benefit_rules['cadence'] ?? '') {
            'QUARTERLY' => now()->year.'-Q'.now()->quarter,
            'SEMIANNUAL' => now()->year.'-H'.(now()->month <= 6 ? 1 : 2),
            'MILESTONE' => 'MILESTONES',
            'ON_DEMAND' => now()->year.'-REQUEST-1',
            default => (string) now()->year,
        };
    }

    public function createSchedule(BenefitScheduling $service): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        $data = $this->validate([
            'form.program_id' => ['required', 'integer', 'exists:programs,id'], 'form.period_key' => ['required', 'string', 'max:40'],
            'form.eligibility_on' => ['required', 'date', 'before_or_equal:today'],
            'form.scheduled_at' => ['required', 'date', 'after_or_equal:form.eligibility_on'],
            'form.location' => ['required', 'string', 'max:250'], 'form.batch_name' => ['nullable', 'string', 'max:100'],
            'form.barangay_ids' => ['array'], 'form.barangay_ids.*' => ['integer', 'distinct', 'exists:barangays,id'],
            'form.requirements' => ['nullable', 'string', 'max:1500'], 'form.allow_representative' => ['required', 'boolean'],
            'form.amount' => ['nullable', 'numeric', 'min:0.01', 'max:9999999999.99'], 'form.available_funds' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
        ])['form'];
        $data['amount'] = $data['amount'] === '' ? null : $data['amount'];
        $data['barangay_ids'] = array_map('intval', $data['barangay_ids']);
        $schedule = $service->create(Program::findOrFail($data['program_id']), $data);
        $this->selectedSchedule = $schedule->id;
        $this->resetPage();
    }

    public function openSchedule(int $id): void
    {
        BenefitSchedule::findOrFail($id);
        $this->selectedSchedule = $id;
    }

    public function render()
    {
        return view('livewire.benefits.workspace', [
            'programs' => Program::query()->whereNotNull('benefit_code')->where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name']),
            'barangays' => Barangay::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'schedules' => BenefitSchedule::query()->with('program:id,name,government_level')->withCount('entitlements')->latest('id')->paginate(10),
        ]);
    }
}
