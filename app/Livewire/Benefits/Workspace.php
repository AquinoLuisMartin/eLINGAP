<?php

namespace App\Livewire\Benefits;

use App\Models\Barangay;
use App\Models\BenefitSchedule;
use App\Models\Program;
use App\Services\Benefits\BenefitAudit;
use App\Services\Benefits\BenefitScheduling;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Workspace extends Component
{
    use WithPagination;

    #[Locked]
    public ?int $selectedSchedule = null;

    #[Locked]
    public bool $payoutMode = false;

    public array $form = ['program_id' => '', 'period_key' => '', 'eligibility_on' => '', 'scheduled_at' => '', 'location' => '', 'batch_name' => '', 'barangay_ids' => [], 'requirements' => '', 'allow_representative' => false, 'amount' => '', 'available_funds' => '0'];

    public array $newProgram = ['name' => '', 'agency' => 'OSCA Santa Maria', 'government_level' => 'LGU', 'cadence' => 'ANNUAL'];

    public bool $historicalClaimsChecked = false;

    public function boot(): void
    {
        Gate::authorize('viewAny', BenefitSchedule::class);
    }

    public function mount(?string $initialProgramId = null, bool $payoutMode = false, ?int $initialScheduleId = null): void
    {
        $this->form['eligibility_on'] = today()->toDateString();
        $this->form['scheduled_at'] = now()->format('Y-m-d\TH:i');
        $this->form['period_key'] = (string) now()->year;
        $this->payoutMode = $payoutMode;
        if ($initialProgramId !== null && $initialScheduleId === null) {
            $this->form['program_id'] = $initialProgramId;
            $this->updatedFormProgramId();
        }
        if ($initialScheduleId !== null) {
            abort_unless(BenefitSchedule::query()->whereKey($initialScheduleId)->where('program_id', $initialProgramId)->exists(), 404);
            $this->form['program_id'] = $initialProgramId;
            $this->selectedSchedule = $initialScheduleId;
        }
    }

    public function updatedFormProgramId(): void
    {
        $this->historicalClaimsChecked = false;
        $this->resetValidation();
        if ($this->form['program_id'] === 'other') {
            $this->form['amount'] = '';
            $this->setPeriod($this->newProgram['cadence']);

            return;
        }
        $this->validateOnly('form.program_id', ['form.program_id' => ['nullable', 'integer', $this->availableProgramRule()]]);
        $program = filled($this->form['program_id']) ? Program::find($this->form['program_id']) : null;
        $this->form['amount'] = $program?->default_amount ?? '';
        $this->setPeriod($program?->benefit_rules['cadence'] ?? '');
    }

    public function updatedNewProgramCadence(): void
    {
        $this->validateOnly('newProgram.cadence', ['newProgram.cadence' => ['required', 'in:ANNUAL,QUARTERLY,SEMIANNUAL,ON_DEMAND']]);
        if ($this->form['program_id'] === 'other') {
            $this->setPeriod($this->newProgram['cadence']);
        }
    }

    private function availableProgramRule(): Exists
    {
        return Rule::exists('programs', 'id')->whereNotNull('benefit_code')->whereNotNull('benefit_rules')->whereIn('status', ['ACTIVE', 'UPCOMING']);
    }

    private function setPeriod(string $cadence): void
    {
        $this->form['period_key'] = match ($cadence) {
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
            'form.program_id' => $this->form['program_id'] === 'other' ? ['required', 'in:other'] : ['required', 'integer', $this->availableProgramRule()],
            'form.period_key' => ['required', 'string', 'max:40'],
            'form.eligibility_on' => ['required', 'date', 'before_or_equal:today'],
            'form.scheduled_at' => ['required', 'date', 'after_or_equal:form.eligibility_on'],
            'form.location' => ['required', 'string', 'max:250'], 'form.batch_name' => ['nullable', 'string', 'max:100'],
            'form.barangay_ids' => ['array'], 'form.barangay_ids.*' => ['integer', 'distinct', 'exists:barangays,id'],
            'form.requirements' => ['nullable', 'string', 'max:1500'], 'form.allow_representative' => ['required', 'boolean'],
            'form.amount' => ['nullable', 'numeric', 'min:0.01', 'max:9999999999.99'], 'form.available_funds' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
        ])['form'];
        $data['amount'] = $data['amount'] === '' ? null : $data['amount'];
        $data['barangay_ids'] = array_map('intval', $data['barangay_ids']);
        $custom = [];
        if ($data['program_id'] === 'other') {
            foreach (['name', 'agency'] as $field) {
                if (is_string($this->newProgram[$field] ?? null)) {
                    $this->newProgram[$field] = trim($this->newProgram[$field]);
                }
            }
            $custom = $this->validate([
                'newProgram.name' => ['required', 'string', 'max:180'],
                'newProgram.agency' => ['required', 'string', 'max:180'],
                'newProgram.government_level' => ['required', 'in:LGU,PROVINCIAL,NATIONAL'],
                'newProgram.cadence' => ['required', 'in:ANNUAL,QUARTERLY,SEMIANNUAL,ON_DEMAND'],
            ])['newProgram'];
        }
        $schedule = DB::transaction(function () use ($service, $data, $custom) {
            if ($custom) {
                $this->validate(['historicalClaimsChecked' => ['accepted']]);
                DB::select('SELECT pg_advisory_xact_lock(194701, 2)');
                if (Program::query()->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($custom['name'])])->exists()) {
                    throw ValidationException::withMessages(['newProgram.name' => 'This program already exists. Select it from the list or configure it in Program rules.']);
                }
                $program = Program::create([
                    'name' => $custom['name'], 'agency' => $custom['agency'], 'government_level' => $custom['government_level'],
                    'benefit_code' => 'custom-'.Str::ulid(), 'benefit_rules' => ['cadence' => $custom['cadence']],
                    'default_amount' => $data['amount'], 'status' => 'ACTIVE', 'created_by' => auth()->id(),
                ]);
                BenefitAudit::record('benefit.program_configured', $program, ['rules' => $program->benefit_rules, 'historical_claims_checked' => true]);
            } else {
                $program = Program::query()->lockForUpdate()->findOrFail($data['program_id']);
                if ($program->status === 'UPCOMING') {
                    $this->validate(['historicalClaimsChecked' => ['accepted']]);
                    $program->update(['status' => 'ACTIVE']);
                    BenefitAudit::record('benefit.program_configured', $program, ['rules' => $program->benefit_rules, 'historical_claims_checked' => true]);
                }
            }

            return $service->create($program, $data);
        });
        $this->form['program_id'] = (string) $schedule->program_id;
        $this->reset('newProgram', 'historicalClaimsChecked');
        $this->selectedSchedule = $schedule->id;
        $this->resetPage('schedulesPage');
    }

    public function openSchedule(int $id): void
    {
        BenefitSchedule::findOrFail($id);
        $this->selectedSchedule = $id;
    }

    public function render(): View
    {
        $programs = Program::query()->whereNotNull('benefit_code')->whereNotNull('benefit_rules')->whereIn('status', ['ACTIVE', 'UPCOMING'])->orderBy('name')->get(['id', 'name', 'status', 'benefit_rules', 'provisional']);

        return view('livewire.benefits.workspace', [
            'programs' => $programs,
            'selectedProgram' => $programs->firstWhere('id', $this->form['program_id']),
            'barangays' => Barangay::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'schedules' => $this->payoutMode ? null : BenefitSchedule::query()->with('program:id,name,government_level')->withCount('entitlements')->latest('id')->paginate(10, pageName: 'schedulesPage'),
        ]);
    }
}
