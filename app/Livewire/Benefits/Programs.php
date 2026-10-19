<?php

namespace App\Livewire\Benefits;

use App\Models\BenefitSchedule;
use App\Models\Program;
use App\Services\Benefits\BenefitAudit;
use Database\Seeders\BenefitProgramSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Programs extends Component
{
    #[Locked]
    public ?int $editing = null;

    public array $form = ['name' => '', 'agency' => 'OSCA Santa Maria', 'government_level' => 'LGU', 'status' => 'UPCOMING', 'default_amount' => '', 'cadence' => 'ANNUAL', 'cutoff' => '', 'payout_month' => '', 'milestones' => '', 'claim_window_months' => '', 'october_conditions' => false, 'office_review' => false, 'groceries' => false, 'provisional' => false];

    public bool $historicalClaimsChecked = false;

    public string $notice = '';

    public function boot(): void
    {
        Gate::authorize('viewAny', BenefitSchedule::class);
    }

    public function installCatalog(): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        DB::transaction(function (): void {
            (new BenefitProgramSeeder)->run();
            BenefitAudit::record('benefit.catalog_initialized', Program::query()->whereNotNull('benefit_code')->firstOrFail());
        });
        $this->notice = 'Approved program definitions added. Review provisional rules and historical claims before activation.';
    }

    public function edit(int $id): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        $program = Program::findOrFail($id);
        $rules = $program->benefit_rules ?? ['cadence' => 'ANNUAL'];
        $this->editing = $id;
        $this->form = [
            'name' => $program->name, 'agency' => $program->agency, 'government_level' => $program->government_level,
            'status' => $program->status, 'default_amount' => $program->default_amount ?? '',
            'cadence' => $rules['cadence'], 'cutoff' => $rules['cutoff'] ?? '', 'payout_month' => $rules['payout_month'] ?? '',
            'milestones' => collect($rules['milestones'] ?? [])->map(fn ($amount, $age) => $age.':'.$amount)->implode("\n"),
            'claim_window_months' => $rules['claim_window_months'] ?? '', 'october_conditions' => $rules['october_conditions'] ?? false,
            'office_review' => $rules['office_review'] ?? false, 'groceries' => $rules['groceries'] ?? false, 'provisional' => $program->provisional,
        ];
        $this->historicalClaimsChecked = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        $this->validate([
            'form.name' => ['required', 'string', 'max:180'], 'form.agency' => ['required', 'string', 'max:180'],
            'form.government_level' => ['required', 'in:LGU,PROVINCIAL,NATIONAL'], 'form.status' => ['required', 'in:ACTIVE,UPCOMING,INACTIVE'],
            'form.default_amount' => ['nullable', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'form.cadence' => ['required', 'in:ANNUAL,QUARTERLY,SEMIANNUAL,MILESTONE,ON_DEMAND'],
            'form.cutoff' => ['nullable', 'date_format:m-d'], 'form.payout_month' => ['nullable', 'integer', 'between:1,12'],
            'form.milestones' => ['nullable', 'string', 'max:1000'], 'form.claim_window_months' => ['nullable', 'integer', 'between:1,120'],
            'form.october_conditions' => ['boolean'], 'form.office_review' => ['boolean'], 'form.groceries' => ['boolean'], 'form.provisional' => ['boolean'],
            'historicalClaimsChecked' => ['accepted'],
        ]);
        $rules = ['cadence' => $this->form['cadence'], 'october_conditions' => $this->form['october_conditions'], 'office_review' => $this->form['office_review'], 'groceries' => $this->form['groceries']];
        foreach (['cutoff', 'payout_month', 'claim_window_months'] as $key) {
            if ($this->form[$key] !== '') {
                $rules[$key] = $key === 'cutoff' ? $this->form[$key] : (int) $this->form[$key];
            }
        }
        if ($rules['cadence'] === 'MILESTONE') {
            $rules['milestones'] = [];
            foreach (preg_split('/\R/', trim($this->form['milestones'])) as $line) {
                if (! preg_match('/^(\d{2,3}):(\d+(?:\.\d{1,2})?)$/', trim($line), $match) || (int) $match[1] < 60 || (int) $match[1] > 120 || (float) $match[2] <= 0 || (float) $match[2] > 9999999999.99) {
                    $this->addError('form.milestones', 'Use one age:amount per line, such as 90:5000.');

                    return;
                }
                $rules['milestones'][(int) $match[1]] = $match[2];
            }
        }
        DB::transaction(function () use ($rules): void {
            $program = $this->editing ? Program::query()->lockForUpdate()->findOrFail($this->editing) : new Program;
            $program->fill(collect($this->form)->only(['name', 'agency', 'government_level', 'status', 'provisional'])->all());
            $program->fill(['benefit_code' => $program->benefit_code ?? 'custom-'.Str::ulid(), 'benefit_rules' => $rules, 'default_amount' => $this->form['default_amount'] ?: null]);
            if ($program->exists && ! $program->isDirty()) {
                return;
            }
            $program->save();
            BenefitAudit::record('benefit.program_configured', $program, ['rules' => $rules, 'default_amount' => $program->default_amount, 'historical_claims_checked' => true]);
        });
        $this->reset('form', 'editing', 'historicalClaimsChecked');
        $this->notice = 'Program saved. Existing entitlement and payout amounts remain unchanged.';
    }

    public function render()
    {
        return view('livewire.benefits.programs', ['programs' => Program::query()->whereNotNull('benefit_code')->orderBy('government_level')->orderBy('name')->get()]);
    }
}
