<?php

namespace App\Services\Benefits;

use App\Jobs\Benefits\PrepareBenefitRecipients;
use App\Models\BenefitEntitlement;
use App\Models\BenefitSchedule;
use App\Models\BenefitScheduleRecipient;
use App\Models\SeniorCitizen;
use Illuminate\Support\Facades\DB;

class BenefitRecipientPreparation
{
    public function __construct(private BenefitEligibility $eligibility) {}

    public function process(int $scheduleId, string $token, int $cursor, int $requestedBy, bool $queueNext): ?int
    {
        return DB::transaction(function () use ($scheduleId, $token, $cursor, $requestedBy, $queueNext): ?int {
            $schedule = BenefitSchedule::query()->lockForUpdate()->findOrFail($scheduleId);
            if ($schedule->status !== 'DRAFT' || $schedule->preparation_token !== $token || (int) $schedule->preparation_cursor !== $cursor) {
                return null;
            }
            $seniors = SeniorCitizen::query()->where('id', '>', $cursor)->where('id', '<=', $schedule->preparation_max_id)
                ->where('status', 'VERIFIED')->whereNotNull('id_generated_at')
                ->when($schedule->barangay_ids, fn ($query) => $query->whereIn('barangay_id', $schedule->barangay_ids))
                ->orderBy('id')->limit(100)->get();
            $periods = $this->eligibility->periods($schedule->rule_snapshot, $schedule->eligibility_on, $schedule->period_key);
            $rows = [];
            foreach ($seniors as $senior) {
                foreach ($periods as $period) {
                    $on = $this->eligibility->periodDate($schedule->rule_snapshot, $schedule->eligibility_on, $period['key']);
                    if ($this->eligibility->reason($senior, $schedule->rule_snapshot, $on, $period['age'])) {
                        continue;
                    }
                    $rows[] = [
                        'program_id' => $schedule->program_id, 'senior_citizen_id' => $senior->id, 'period_key' => $period['key'],
                        'milestone_age' => $period['age'], 'eligibility_on' => $on->toDateString(),
                        'amount' => $period['age'] ? $schedule->rule_snapshot['milestones'][$period['age']] : $schedule->amount,
                        'rule_snapshot' => json_encode($schedule->rule_snapshot, JSON_THROW_ON_ERROR),
                        'created_at' => now(), 'updated_at' => now(),
                    ];
                }
            }
            if ($rows) {
                BenefitEntitlement::query()->insertOrIgnore($rows);
                $entries = BenefitEntitlement::query()->where('program_id', $schedule->program_id)
                    ->whereIn('senior_citizen_id', array_column($rows, 'senior_citizen_id'))
                    ->whereIn('period_key', array_column($rows, 'period_key'))
                    ->whereNull('benefit_schedule_id')->where('status', '!=', 'CLAIMED')
                    ->whereNotIn('id', BenefitScheduleRecipient::query()->select('benefit_entitlement_id')->where('benefit_schedule_id', $scheduleId))
                    ->orderBy('id')->lockForUpdate()->get();
                $eligibleKeys = array_fill_keys(array_map(fn ($row) => $row['senior_citizen_id'].':'.$row['period_key'], $rows), true);
                $entries = $entries->filter(fn ($entry) => isset($eligibleKeys[$entry->senior_citizen_id.':'.$entry->period_key]));
                if ($entries->isNotEmpty()) {
                    foreach (array_unique(array_column($rows, 'period_key')) as $periodKey) {
                        $amount = collect($rows)->firstWhere('period_key', $periodKey)['amount'];
                        BenefitEntitlement::query()->whereIn('id', $entries->modelKeys())->where('period_key', $periodKey)->whereNull('amount')->update(['amount' => $amount]);
                    }
                    BenefitEntitlement::query()->whereIn('id', $entries->modelKeys())->update(['benefit_schedule_id' => $scheduleId]);
                    BenefitScheduleRecipient::query()->insertOrIgnore($entries->map(fn ($entry) => [
                        'benefit_schedule_id' => $scheduleId, 'benefit_entitlement_id' => $entry->id, 'created_at' => now(), 'updated_at' => now(),
                    ])->all());
                }
            }
            if ($seniors->count() < 100) {
                $schedule->forceFill(['preparation_token' => null])->save();
                BenefitAudit::record('benefit.recipients_prepared', $schedule, [], $requestedBy);

                return null;
            }
            $next = (int) $seniors->last()->id;
            $schedule->forceFill(['preparation_cursor' => $next])->save();
            if ($queueNext) {
                PrepareBenefitRecipients::dispatch($scheduleId, $token, $next, $requestedBy)->onConnection('database-atomic')->beforeCommit();
            }

            return $next;
        }, 3);
    }
}
