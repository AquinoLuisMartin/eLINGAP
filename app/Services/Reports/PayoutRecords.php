<?php

namespace App\Services\Reports;

use App\Models\BenefitEntitlement;
use App\Models\BenefitPayment;
use App\Models\Payout;
use App\Models\SeniorCitizen;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayoutRecords
{
    public const STATUSES = [
        '' => 'All statuses',
        'PENDING' => 'Pending',
        'UNCLAIMED' => 'Unclaimed',
        'CLAIMED' => 'Claimed',
    ];

    public const SORTS = [
        'name_asc' => 'Last Name (A–Z)',
        'name_desc' => 'Last Name (Z–A)',
        'pending_first' => 'Pending / Unclaimed First',
        'claimed_first' => 'Claimed First',
    ];

    public function query(array $filters): Builder
    {
        $legacy = Payout::query()->join('payout_schedules', 'payout_schedules.id', '=', 'payouts.payout_schedule_id')
            ->select(['payouts.id', 'payouts.senior_citizen_id', 'payout_schedules.program_id', 'payouts.amount', 'payouts.status', 'payouts.claimant_type', 'payouts.created_at'])
            ->selectRaw("'legacy' as source");
        $benefits = BenefitEntitlement::query()
            ->select(['benefit_entitlements.id', 'benefit_entitlements.senior_citizen_id', 'benefit_entitlements.program_id', 'benefit_entitlements.amount', 'benefit_entitlements.status'])
            ->addSelect(['claimant_type' => BenefitPayment::query()->select('claimant_type')
                ->whereColumn('benefit_entitlement_id', 'benefit_entitlements.id')->whereNull('reversed_at')->latest('id')->limit(1)])
            ->addSelect('benefit_entitlements.created_at')->selectRaw("'benefit' as source");

        return DB::query()->fromSub($legacy->unionAll($benefits), 'records')
            ->when(filled($filters['search'] ?? null), fn ($query) => $query->whereIn('senior_citizen_id', SeniorCitizen::query()->select('id')->searchable($filters['search'])))
            ->when($filters['status'] ?? null, function ($query, $status) {
                return match ($status) {
                    'PENDING' => $query->whereIn('status', ['PENDING', 'UNPAID']),
                    'CLAIMED' => $query->whereIn('status', ['CLAIMED', 'RELEASED']),
                    default => $query->where('status', $status),
                };
            })
            ->when($filters['program_id'] ?? null, fn ($query, $id) => $query->where('program_id', $id))
            ->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->whereIn('senior_citizen_id', SeniorCitizen::query()->select('id')->where('barangay_id', $id)));
    }

    public function ordered(Builder $query, string $sort = 'name_asc'): Builder
    {
        if ($sort === 'pending_first') {
            $query->orderByRaw("CASE WHEN status IN ('PENDING', 'UNPAID', 'UNCLAIMED') THEN 0 WHEN status IN ('CLAIMED', 'RELEASED') THEN 1 ELSE 2 END");
        } elseif ($sort === 'claimed_first') {
            $query->orderByRaw("CASE WHEN status IN ('CLAIMED', 'RELEASED') THEN 0 WHEN status IN ('PENDING', 'UNPAID', 'UNCLAIMED') THEN 1 ELSE 2 END");
        }
        foreach (['last_name', 'first_name', 'middle_name'] as $column) {
            $query->orderBy(SeniorCitizen::query()->select($column)->whereColumn('senior_citizens.id', 'records.senior_citizen_id'), $column === 'last_name' && $sort === 'name_desc' ? 'desc' : 'asc');
        }

        return $query->orderBy('source')->orderByDesc('id');
    }

    public function loadDetails(Collection $rows): Collection
    {
        $relationships = ['seniorCitizen:id,barangay_id,osca_id_number,first_name,middle_name,last_name,name_suffix', 'seniorCitizen.barangay:id,name'];
        $legacy = Payout::query()->whereIn('id', $rows->where('source', 'legacy')->pluck('id'))
            ->select(['id', 'payout_schedule_id', 'senior_citizen_id', 'claimant_name', 'amount', 'status'])
            ->with([...$relationships, 'schedule:id,program_id', 'schedule.program:id,name'])->get()->keyBy('id');
        $benefits = BenefitEntitlement::query()->whereIn('id', $rows->where('source', 'benefit')->pluck('id'))
            ->select(['id', 'program_id', 'senior_citizen_id', 'period_key', 'amount', 'status'])
            ->with([...$relationships, 'program:id,name', 'payments' => fn ($query) => $query->whereNull('reversed_at')->select(['id', 'benefit_entitlement_id', 'claimant_name', 'claimant_type'])])->get()->keyBy('id');

        return new ModelCollection($rows->map(function ($row) use ($legacy, $benefits) {
            $record = $row->source === 'legacy' ? $legacy->get($row->id) : $benefits->get($row->id);
            $record->setRelation('program', $row->source === 'legacy' ? $record->schedule->program : $record->program);
            $record->setAttribute('source', $row->source);
            $record->setAttribute('period_key', $row->source === 'benefit' ? $record->period_key : null);
            $record->setAttribute('claimant_name', $row->source === 'legacy' ? $record->claimant_name : ($record->payments->first()?->claimant_name ?: ($row->claimant_type ? ucfirst(strtolower($row->claimant_type)) : null)));
            $record->setAttribute('url', $row->source === 'legacy' ? route('payouts.show', $row->id) : route('senior-citizens.show', $row->senior_citizen_id));

            return $record;
        })->all());
    }
}
