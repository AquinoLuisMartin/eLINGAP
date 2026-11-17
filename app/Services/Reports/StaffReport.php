<?php

namespace App\Services\Reports;

use App\Models\Application;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\Payout;
use App\Models\Program;
use App\Models\SeniorCitizen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StaffReport
{
    public const TYPES = [
        'masterlist' => 'Senior citizen masterlist',
        'beneficiaries' => 'Program beneficiary masterlist',
        'population' => 'Senior population by barangay',
        'applications' => 'Benefit application status log',
        'payouts' => 'Benefits and payout summary',
        'milestones' => 'Milestone age qualifiers',
        'proxy' => 'Proxy payout claims',
        'archived' => 'Deceased and archived seniors log',
    ];

    public function milestoneAges(): Collection
    {
        return Program::query()->where('status', 'ACTIVE')->get(['benefit_rules'])
            ->flatMap(fn ($program) => array_keys($program->benefit_rules['milestones'] ?? []))
            ->map(fn ($age) => (int) $age)->filter(fn ($age) => $age >= 60)->unique()->sort()->values();
    }

    public function query(string $type, array $filters): Builder
    {
        return match ($type) {
            'population' => $this->populationQuery()->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->whereKey($id)),
            'beneficiaries' => Beneficiary::query()->select(['id', 'program_id', 'senior_citizen_id', 'enrolled_on', 'status'])
                ->where('status', 'ACTIVE')
                ->with(['seniorCitizen:id,barangay_id,osca_id_number,first_name,middle_name,last_name,name_suffix,birth_date', 'seniorCitizen.barangay:id,name', 'program:id,name'])
                ->whereHas('seniorCitizen', function ($query) use ($filters) {
                    $query->where('status', 'VERIFIED')
                        ->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->where('barangay_id', $id));
                    $this->ageFilters($query, $filters);
                })
                ->when($filters['program_id'] ?? null, fn ($query, $id) => $query->where('program_id', $id)),
            'masterlist', 'milestones', 'archived' => $this->seniors($type, $filters),
            'applications' => Application::query()->select(['id', 'senior_citizen_id', 'program_id', 'application_number', 'applied_on', 'status'])
                ->with(['seniorCitizen:id,barangay_id,osca_id_number,first_name,middle_name,last_name,name_suffix', 'seniorCitizen.barangay:id,name', 'program:id,name'])
                ->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->whereHas('seniorCitizen', fn ($senior) => $senior->where('barangay_id', $id)))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('applied_on', '>=', $date))
                ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('applied_on', '<=', $date)),
            'payouts', 'proxy' => Payout::query()->select(['id', 'senior_citizen_id', 'payout_schedule_id', 'amount', 'claimant_name', 'status'])
                ->with(['seniorCitizen:id,barangay_id,osca_id_number,first_name,middle_name,last_name,name_suffix', 'seniorCitizen.barangay:id,name', 'schedule:id,program_id', 'schedule.program:id,name'])
                ->when($type === 'proxy', fn ($query) => $query->where('claimant_type', 'PROXY')->where('status', 'RELEASED'))
                ->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->whereHas('seniorCitizen', fn ($senior) => $senior->where('barangay_id', $id)))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when($filters['program_id'] ?? null, fn ($query, $id) => $query->whereHas('schedule', fn ($schedule) => $schedule->where('program_id', $id)))
                ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereHas('schedule', fn ($schedule) => $schedule->whereDate('scheduled_on', '>=', $date)))
                ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereHas('schedule', fn ($schedule) => $schedule->whereDate('scheduled_on', '<=', $date))),
        };
    }

    public function populationQuery(): Builder
    {
        return Barangay::query()->select(['id', 'name'])->withCount([
            'seniorCitizens as population' => fn ($query) => $query->where('status', 'VERIFIED'),
        ]);
    }

    public function orderedQuery(string $type, array $filters): Builder
    {
        $query = $this->query($type, $filters);

        return match ($type) {
            'population' => $query->orderBy('name')->orderBy('id'),
            'masterlist', 'milestones', 'archived' => $query->orderBy('last_name')->orderBy('first_name')->orderBy('id'),
            'beneficiaries' => $query->orderBy(SeniorCitizen::query()->select('last_name')->whereColumn('senior_citizens.id', 'beneficiaries.senior_citizen_id'))
                ->orderBy(SeniorCitizen::query()->select('first_name')->whereColumn('senior_citizens.id', 'beneficiaries.senior_citizen_id'))->orderBy('id'),
            default => $query->orderBy('id'),
        };
    }

    public function filterLabels(array $filters): array
    {
        $labels = [];
        foreach (array_filter($filters, fn ($value) => $value !== null && $value !== '') as $key => $value) {
            $label = match ($key) {
                'barangay_id' => 'Barangay', 'program_id' => 'Program',
                'age_min' => 'Minimum age', 'age_max' => 'Maximum age',
                default => ucfirst(str_replace('_', ' ', $key)),
            };
            $labels[$label] = match ($key) {
                'barangay_id' => Barangay::query()->whereKey($value)->value('name'),
                'program_id' => Program::query()->whereKey($value)->value('name'),
                default => $value,
            };
        }

        return $labels;
    }

    private function ageFilters(Builder $query, array $filters): void
    {
        if (isset($filters['age_min'])) {
            $query->where('birth_date', '<=', today()->subYearsNoOverflow((int) $filters['age_min']));
        }
        if (isset($filters['age_max'])) {
            $query->where('birth_date', '>', today()->subYearsNoOverflow((int) $filters['age_max'] + 1));
        }
    }

    private function seniors(string $type, array $filters): Builder
    {
        return SeniorCitizen::query()->select(['id', 'barangay_id', 'osca_id_number', 'first_name', 'middle_name', 'last_name', 'name_suffix', 'birth_date', 'status'])
            ->with('barangay:id,name')
            ->when($type === 'archived', fn ($query) => $query->whereIn('status', ['ARCHIVED', 'DECEASED', 'TRANSFERRED']))
            ->when($type === 'milestones', function ($query) use ($filters) {
                $age = $filters['milestone'] ?? $this->milestoneAges()->first();
                if ($age === null) {
                    $query->whereRaw('1 = 0');

                    return;
                }
                $age = (int) $age;
                $query->where('status', 'VERIFIED')->where('birth_date', '<=', today()->subYears($age));
            })
            ->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->where('barangay_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->tap(fn ($query) => $this->ageFilters($query, $filters));
    }

    public function headings(string $type): array
    {
        return match ($type) {
            'population' => ['Barangay', 'Verified senior population'],
            'beneficiaries' => ['OSCA ID', 'Last name', 'First name', 'Middle name', 'Suffix', 'Age', 'Barangay', 'Program', 'Enrolled on', 'Assignment status'],
            'masterlist', 'milestones', 'archived' => ['OSCA ID', 'Last name', 'First name', 'Middle name', 'Suffix', 'Birth date', 'Age', 'Barangay', 'Status'],
            'applications' => ['OSCA ID', 'Last name', 'First name', 'Middle name', 'Suffix', 'Reference', 'Barangay', 'Program', 'Applied on', 'Status'],
            'payouts', 'proxy' => ['OSCA ID', 'Last name', 'First name', 'Middle name', 'Suffix', 'Barangay', 'Program', 'Amount', 'Claimant', 'Status'],
        };
    }

    public function row(string $type, mixed $record): array
    {
        if ($type === 'population') {
            return [$record->name, (int) $record->population];
        }
        $senior = $record instanceof SeniorCitizen ? $record : $record->seniorCitizen;
        $identity = [$senior->osca_id_number ?: 'Not assigned', $senior->last_name, $senior->first_name, $senior->middle_name ?? '', $senior->name_suffix ?? ''];

        return [...$identity, ...match ($type) {
            'beneficiaries' => [$senior->birth_date->age, $senior->barangay->name, $record->program->name, $record->enrolled_on->format('Y-m-d'), $record->status],
            'masterlist', 'milestones', 'archived' => [$senior->birth_date->format('Y-m-d'), $senior->birth_date->age, $senior->barangay->name, $senior->status->value],
            'applications' => [$record->application_number, $senior->barangay->name, $record->program->name, $record->applied_on->format('Y-m-d'), $record->status->value],
            'payouts', 'proxy' => [$senior->barangay->name, $record->schedule->program->name, $record->amount, $record->claimant_name ?: 'Not recorded', $record->status->value],
        }];
    }
}
