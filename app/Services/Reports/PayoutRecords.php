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
    public function query(array $filters): Builder
    {
        $legacy = Payout::query()->join('payout_schedules', 'payout_schedules.id', '=', 'payouts.payout_schedule_id')
            ->select(['payouts.id', 'payouts.senior_citizen_id', 'payout_schedules.program_id', 'payouts.amount', 'payouts.status', 'payouts.claimant_type', 'payouts.created_at'])
            ->selectRaw("'legacy' as source");
        $benefits = BenefitEntitlement::query()
            ->select(['id', 'senior_citizen_id', 'program_id', 'amount', 'status'])
            ->addSelect(['claimant_type' => BenefitPayment::query()->select('claimant_type')
                ->whereColumn('benefit_entitlement_id', 'benefit_entitlements.id')->whereNull('reversed_at')->latest('id')->limit(1)])
            ->addSelect('created_at')->selectRaw("'benefit' as source");

        return DB::query()->fromSub($legacy->unionAll($benefits), 'records')
            ->when($filters['search'] ?? null, fn ($query, $term) => $query->whereIn('senior_citizen_id', SeniorCitizen::query()->select('id')->searchable($term)))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['program_id'] ?? null, fn ($query, $id) => $query->where('program_id', $id))
            ->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->whereIn('senior_citizen_id', SeniorCitizen::query()->select('id')->where('barangay_id', $id)));
    }

    public function loadDetails(Collection $rows): Collection
    {
        $relationships = ['seniorCitizen:id,barangay_id,osca_id_number,first_name,middle_name,last_name,name_suffix', 'seniorCitizen.barangay:id,name'];
        $legacy = Payout::query()->whereIn('id', $rows->where('source', 'legacy')->pluck('id'))
            ->select(['id', 'payout_schedule_id', 'senior_citizen_id', 'claimant_name', 'amount', 'status'])
            ->with([...$relationships, 'schedule:id,program_id', 'schedule.program:id,name'])->get()->keyBy('id');
        $benefits = BenefitEntitlement::query()->whereIn('id', $rows->where('source', 'benefit')->pluck('id'))
            ->select(['id', 'program_id', 'senior_citizen_id', 'period_key', 'amount', 'status'])
            ->with([...$relationships, 'program:id,name'])->get()->keyBy('id');

        return new ModelCollection($rows->map(function ($row) use ($legacy, $benefits) {
            $record = $row->source === 'legacy' ? $legacy->get($row->id) : $benefits->get($row->id);
            $record->setRelation('program', $row->source === 'legacy' ? $record->schedule->program : $record->program);
            $record->setAttribute('source', $row->source);
            $record->setAttribute('period_key', $row->source === 'benefit' ? $record->period_key : null);
            $record->setAttribute('claimant_name', $row->source === 'legacy' ? $record->claimant_name : ($row->claimant_type ? ucfirst(strtolower($row->claimant_type)) : null));
            $record->setAttribute('url', $row->source === 'legacy' ? route('payouts.show', $row->id) : route('senior-citizens.show', $row->senior_citizen_id));

            return $record;
        })->all());
    }
}
