<?php

namespace App\Services\Reports;

use App\Models\Application;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Support\PhilippineCurrency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    public function query(string $type, array $filters): Builder|QueryBuilder
    {
        return match ($type) {
            'population' => $this->populationQuery($filters)->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->whereKey($id)),
            'beneficiaries' => Beneficiary::query()->select(['id', 'program_id', 'senior_citizen_id', 'enrolled_on', 'status'])
                ->where('status', 'ACTIVE')
                ->whereIn('id', Beneficiary::query()->selectRaw('min(id)')->where('status', 'ACTIVE')->groupBy('senior_citizen_id'))
                ->selectSub(Beneficiary::query()->selectRaw('count(*)')->whereColumn('senior_citizen_id', 'beneficiaries.senior_citizen_id')->from('beneficiaries as active')->where('active.status', 'ACTIVE'), 'active_assignment_count')
                ->with(['seniorCitizen:id,barangay_id,osca_id_number,first_name,middle_name,last_name,name_suffix,birth_date', 'seniorCitizen.barangay:id,name', 'program:id,name'])
                ->whereHas('seniorCitizen', function ($query) use ($filters) {
                    $query->where('status', 'VERIFIED');
                    $this->seniorFilters($query, $filters);
                })
                ->when($filters['program_id'] ?? null, fn ($query, $id) => $query->where('program_id', $id)),
            'masterlist', 'milestones', 'archived' => $this->seniors($type, $filters),
            'applications' => Application::query()->select(['id', 'senior_citizen_id', 'program_id', 'application_number', 'applied_on', 'status', 'reviewed_at', 'reviewed_by', 'remarks'])
                ->with(['seniorCitizen:id,barangay_id,osca_id_number,first_name,middle_name,last_name,name_suffix', 'seniorCitizen.barangay:id,name', 'program:id,name', 'reviewer:id,first_name,middle_name,last_name,name_suffix', 'statusHistories' => fn ($query) => $query->where('to_status', '!=', 'PENDING')->latest('created_at')->latest('id'), 'statusHistories.changedBy:id,first_name,middle_name,last_name,name_suffix'])
                ->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->whereHas('seniorCitizen', fn ($senior) => $senior->where('barangay_id', $id)))
                ->when($filters['program_id'] ?? null, fn ($query, $id) => $query->where('program_id', $id))
                ->when(filled($filters['search'] ?? null), fn ($query) => $query->where(fn ($query) => $query->whereHas('seniorCitizen', fn ($senior) => $senior->searchable($filters['search']))->orWhere('application_number', 'ilike', '%'.addcslashes($filters['search'], '\\%_').'%')))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('applied_on', '>=', $date))
                ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('applied_on', '<=', $date)),
            'payouts', 'proxy' => $this->payoutQuery($type, $filters),
        };
    }

    private function payoutQuery(string $type, array $filters): QueryBuilder
    {
        $query = DB::query()->fromSub(app(PayoutRecords::class)->query($filters), 'records')
            ->join('senior_citizens as senior', 'senior.id', '=', 'records.senior_citizen_id')
            ->join('barangays as barangay', 'barangay.id', '=', 'senior.barangay_id')
            ->join('programs as program', 'program.id', '=', 'records.program_id')
            ->leftJoin('payouts as legacy', fn ($join) => $join->on('legacy.id', '=', 'records.id')->where('records.source', 'legacy'))
            ->leftJoin('payout_schedules as legacy_schedule', 'legacy_schedule.id', '=', 'legacy.payout_schedule_id')
            ->leftJoin('benefit_entitlements as entitlement', fn ($join) => $join->on('entitlement.id', '=', 'records.id')->where('records.source', 'benefit'))
            ->leftJoin('benefit_schedules as schedule', 'schedule.id', '=', 'entitlement.benefit_schedule_id')
            ->leftJoin('benefit_payments as claim', fn ($join) => $join->on('claim.benefit_entitlement_id', '=', 'entitlement.id')->whereNull('claim.reversed_at'))
            ->select(['records.id', 'records.senior_citizen_id', 'records.program_id', 'records.status', 'records.claimant_type', 'records.created_at', 'records.source', 'senior.osca_id_number', 'senior.last_name', 'senior.first_name', 'senior.middle_name', 'senior.name_suffix', 'barangay.name as barangay_name', 'program.name as program_name'])
            ->selectRaw('coalesce(claim.amount, records.amount) as amount')
            ->selectRaw('coalesce(legacy.claimant_name, claim.claimant_name) as claimant_name')
            ->selectRaw('coalesce(claim.paid_at, legacy.released_at, schedule.scheduled_at, legacy_schedule.scheduled_on::timestamp AT TIME ZONE ?, entitlement.eligibility_on::timestamp AT TIME ZONE ?) as payout_date', [config('app.timezone'), config('app.timezone')])
            ->when($type === 'proxy', fn ($query) => $query->whereIn('records.status', ['CLAIMED', 'RELEASED'])->whereIn('records.claimant_type', ['PROXY', 'REPRESENTATIVE']));

        return DB::query()->fromSub($query, 'report_rows')
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereRaw('(payout_date AT TIME ZONE ?)::date >= ?::date', [config('app.timezone'), $date]))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereRaw('(payout_date AT TIME ZONE ?)::date <= ?::date', [config('app.timezone'), $date]));
    }

    public function populationQuery(array $filters = []): Builder
    {
        return Barangay::query()->select(['id', 'name'])->withCount([
            'seniorCitizens as population' => fn ($query) => $query->where('status', 'VERIFIED')->tap(fn ($query) => $this->seniorFilters($query, $filters)),
        ]);
    }

    public function summary(string $type, array $filters): array
    {
        if ($type === 'population') {
            return ['Matching verified population' => (int) $this->query($type, $filters)->get()->sum('population')];
        }
        if (! in_array($type, ['beneficiaries', 'payouts', 'proxy'], true)) {
            return [];
        }
        $query = DB::query()->fromSub($this->query($type, $filters), 'matching');
        if ($type === 'beneficiaries') {
            $totals = $query->selectRaw('count(case when active_assignment_count = 1 then 1 end) as confirmed, count(case when active_assignment_count > 1 then 1 end) as unresolved')->first();

            return ['Confirmed assignments' => (int) $totals->confirmed, 'Program selection required' => (int) $totals->unresolved];
        }
        $totals = $query->selectRaw("coalesce(sum(amount), 0) as amount, coalesce(sum(case when status in ('CLAIMED', 'RELEASED') then amount else 0 end), 0) as claimed_amount, count(case when status in ('CLAIMED', 'RELEASED') then 1 end) as claimed, count(case when status in ('PENDING', 'UNPAID', 'UNCLAIMED') then 1 end) as outstanding")->first();

        return ['Payout amount' => PhilippineCurrency::format($totals->amount), 'Claimed amount' => PhilippineCurrency::format($totals->claimed_amount), 'Claimed records' => (int) $totals->claimed, 'Pending / unclaimed records' => (int) $totals->outstanding];
    }

    public function orderedQuery(string $type, array $filters): Builder|QueryBuilder
    {
        $query = $this->query($type, $filters);

        return match ($type) {
            'population' => $query->orderBy('name')->orderBy('id'),
            'payouts', 'proxy' => $query->orderBy('last_name')->orderBy('first_name')->orderBy('middle_name')->orderBy('source')->orderBy('id'),
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

    private function seniorFilters(Builder $query, array $filters): void
    {
        $query->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->where('barangay_id', $id))
            ->when(filled($filters['search'] ?? null), fn ($query) => $query->searchable($filters['search']))
            ->when($filters['program_id'] ?? null, fn ($query, $id) => $query->has('activeAssignments', '=', 1)->whereHas('activeAssignments', fn ($query) => $query->where('program_id', $id)));
        $this->ageFilters($query, $filters);
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
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->tap(fn ($query) => $this->seniorFilters($query, $filters));
    }

    public function headings(string $type): array
    {
        return match ($type) {
            'population' => ['Barangay', 'Verified senior population'],
            'beneficiaries' => ['OSCA ID', 'Last name', 'First name', 'Middle name', 'Suffix', 'Age', 'Barangay', 'Program', 'Enrolled on', 'Assignment status'],
            'masterlist', 'milestones', 'archived' => ['OSCA ID', 'Last name', 'First name', 'Middle name', 'Suffix', 'Birth date', 'Age', 'Barangay', 'Status'],
            'applications' => ['OSCA ID', 'Last name', 'First name', 'Middle name', 'Suffix', 'Reference', 'Barangay', 'Program', 'Applied on', 'Status', 'Reviewed on', 'Processed by', 'Decision notes'],
            'payouts', 'proxy' => ['OSCA ID', 'Last name', 'First name', 'Middle name', 'Suffix', 'Barangay', 'Program', 'Amount', 'Claimant', 'Status'],
        };
    }

    public function row(string $type, mixed $record): array
    {
        if ($type === 'population') {
            return [$record->name, (int) $record->population];
        }
        if (in_array($type, ['payouts', 'proxy'], true)) {
            return [$record->osca_id_number ?: 'Not assigned', $record->last_name, $record->first_name, $record->middle_name ?? '', $record->name_suffix ?? '', $record->barangay_name, $record->program_name, $record->amount ?? 'Not yet assigned', $record->claimant_name ?: 'Not recorded', $record->status];
        }
        $senior = $record instanceof SeniorCitizen ? $record : $record->seniorCitizen;
        $identity = [$senior->osca_id_number ?: 'Not assigned', $senior->last_name, $senior->first_name, $senior->middle_name ?? '', $senior->name_suffix ?? ''];
        $resolved = $type !== 'beneficiaries' || (int) $record->active_assignment_count === 1;

        return [...$identity, ...match ($type) {
            'beneficiaries' => [$senior->birth_date->age, $senior->barangay->name, $resolved ? $record->program->name : 'Program selection required', $resolved ? $record->enrolled_on->format('Y-m-d') : 'Not confirmed', $resolved ? $record->status : 'SELECTION REQUIRED'],
            'masterlist', 'milestones', 'archived' => [$senior->birth_date->format('Y-m-d'), $senior->birth_date->age, $senior->barangay->name, $senior->status->value],
            'applications' => [$record->application_number, $senior->barangay->name, $record->program->name, $record->applied_on->format('Y-m-d'), $record->status->value, ($record->reviewed_at ?? $record->statusHistories->first()?->created_at)?->format('M j, Y g:i A') ?? 'Not recorded', $record->reviewer?->full_name ?? $record->statusHistories->first()?->changedBy?->full_name ?? 'Not recorded', $record->remarks ?? $record->statusHistories->first()?->remarks ?? ''],
        }];
    }
}
