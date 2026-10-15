<?php

namespace App\Services\Benefits;

use App\Enums\BenefitStatus;
use App\Models\BenefitEntitlement;
use App\Models\BenefitSchedule;
use App\Models\BenefitScheduleRecipient;
use App\Models\Program;
use App\Models\SeniorCitizen;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BenefitScheduling
{
    public function __construct(private BenefitEligibility $eligibility, private BenefitAssignment $assignments) {}

    public function create(Program $program, array $data): BenefitSchedule
    {
        Gate::authorize('manage', BenefitSchedule::class);

        return DB::transaction(function () use ($program, $data) {
            $program = Program::query()->lockForUpdate()->findOrFail($program->id);
            abort_unless($program->benefit_rules && $program->status === 'ACTIVE', 422, 'Activate and configure the program first.');
            $on = Carbon::parse($data['eligibility_on']);
            $this->eligibility->periods($program->benefit_rules, $on, $data['period_key']);
            if (isset($program->benefit_rules['payout_month']) && (int) Carbon::parse($data['scheduled_at'])->month !== (int) $program->benefit_rules['payout_month']) {
                throw ValidationException::withMessages(['scheduled_at' => 'Schedule this program in its configured payout month.']);
            }
            $snapshot = [...$program->benefit_rules, 'government_level' => $program->government_level, 'program_name' => $program->name];
            $schedule = BenefitSchedule::create([...$data, 'program_id' => $program->id, 'reference_number' => 'SCH-'.Str::ulid(), 'rule_snapshot' => $snapshot, 'created_by' => auth()->id()]);
            $this->prepare($schedule);
            BenefitAudit::record('benefit.schedule_created', $schedule, ['program_id' => $program->id, 'period' => $schedule->period_key]);

            return $schedule;
        });
    }

    public function prepare(BenefitSchedule $schedule): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        DB::transaction(function () use ($schedule): void {
            Program::query()->lockForUpdate()->findOrFail($schedule->program_id);
            $schedule = BenefitSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            abort_unless($schedule->status === 'DRAFT', 409, 'Only draft schedules may generate recipients.');
            $periods = $this->eligibility->periods($schedule->rule_snapshot, $schedule->eligibility_on, $schedule->period_key);
            SeniorCitizen::query()->where('status', 'VERIFIED')->whereNotNull('id_generated_at')
                ->when($schedule->barangay_ids, fn ($query) => $query->whereIn('barangay_id', $schedule->barangay_ids))
                ->chunkById(200, function ($seniors) use ($schedule, $periods): void {
                    foreach ($seniors as $senior) {
                        foreach ($periods as $period) {
                            $on = $this->eligibility->periodDate($schedule->rule_snapshot, $schedule->eligibility_on, $period['key']);
                            if ($this->eligibility->reason($senior, $schedule->rule_snapshot, $on, $period['age'])) {
                                continue;
                            }
                            $amount = $period['age'] ? $schedule->rule_snapshot['milestones'][$period['age']] : $schedule->amount;
                            $entitlement = BenefitEntitlement::firstOrCreate([
                                'program_id' => $schedule->program_id, 'senior_citizen_id' => $senior->id, 'period_key' => $period['key'],
                            ], ['milestone_age' => $period['age'], 'eligibility_on' => $on, 'amount' => $amount, 'rule_snapshot' => $schedule->rule_snapshot]);
                            $entitlement = BenefitEntitlement::query()->lockForUpdate()->findOrFail($entitlement->id);
                            if ($entitlement->status === BenefitStatus::Claimed || $entitlement->benefit_schedule_id || $schedule->recipients()->where('benefit_entitlement_id', $entitlement->id)->exists()) {
                                continue;
                            }
                            $entitlement->update(['benefit_schedule_id' => $schedule->id, 'amount' => $entitlement->amount ?? $amount]);
                            BenefitScheduleRecipient::create(['benefit_schedule_id' => $schedule->id, 'benefit_entitlement_id' => $entitlement->id]);
                        }
                    }
                });
            BenefitAudit::record('benefit.recipients_prepared', $schedule);
        });
    }

    public function review(BenefitEntitlement $entitlement, bool $include, string $note): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        DB::transaction(function () use ($entitlement, $include, $note): void {
            $schedule = BenefitSchedule::query()->lockForUpdate()->findOrFail($entitlement->benefit_schedule_id);
            $entitlement = BenefitEntitlement::query()->lockForUpdate()->findOrFail($entitlement->id);
            abort_unless($schedule->status === 'DRAFT' && $entitlement->benefit_schedule_id === $schedule->id, 409);
            if ($include) {
                $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($entitlement->senior_citizen_id);
                if ($reason = $this->eligibility->reason($senior, $entitlement->rule_snapshot, $entitlement->eligibility_on, $entitlement->milestone_age)) {
                    throw ValidationException::withMessages(['review' => $reason]);
                }
                $this->assignments->assign($senior, $schedule->program);
            } else {
                abort_if(trim($note) === '', 422, 'An exclusion reason is required.');
                $schedule->recipients()->where('benefit_entitlement_id', $entitlement->id)->update(['outcome' => 'EXCLUDED']);
            }
            $entitlement->update(['reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'review_note' => $note, 'benefit_schedule_id' => $include ? $schedule->id : null]);
            BenefitAudit::record('benefit.recipient_reviewed', $entitlement, ['included' => $include, 'schedule_id' => $schedule->id, 'note' => $note]);
        });
    }

    public function approve(BenefitSchedule $schedule): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        DB::transaction(function () use ($schedule): void {
            $schedule = BenefitSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            abort_unless($schedule->status === 'DRAFT', 409);
            $entries = $schedule->entitlements()->with('seniorCitizen')->lockForUpdate()->get();
            if ($entries->isEmpty() || $entries->contains(fn ($entry) => ! $entry->reviewed_at || $entry->amount === null)) {
                throw ValidationException::withMessages(['approval' => 'Review every included recipient and set an amount before approval.']);
            }
            foreach ($entries as $entry) {
                if ($reason = $this->eligibility->reason($entry->seniorCitizen, $entry->rule_snapshot, $entry->eligibility_on, $entry->milestone_age)) {
                    throw ValidationException::withMessages(['approval' => $reason]);
                }
            }
            if (round($entries->sum(fn ($entry) => (float) $entry->amount), 2) > (float) $schedule->available_funds) {
                throw ValidationException::withMessages(['approval' => 'Available funds cannot cover this list. Exclude recipients to keep their benefits pending.']);
            }
            $schedule->update(['status' => 'APPROVED', 'approved_at' => now(), 'approved_by' => auth()->id()]);
            $schedule->entitlements()->update(['status' => BenefitStatus::Unpaid->value]);
            BenefitAudit::record('benefit.schedule_approved', $schedule, ['recipient_count' => $entries->count(), 'amount' => $entries->sum('amount')]);
        });
    }

    public function fund(BenefitSchedule $schedule, ?string $amount, string $funds): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        validator(['amount' => $amount, 'funds' => $funds], [
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'funds' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
        ])->validate();
        DB::transaction(function () use ($schedule, $amount, $funds): void {
            $schedule = BenefitSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            abort_unless($schedule->status === 'DRAFT', 409, 'Approved funding and amounts are immutable.');
            $schedule->update(['amount' => $amount, 'available_funds' => $funds]);
            if ($amount !== null) {
                $schedule->entitlements()->whereNull('amount')->update(['amount' => $amount]);
            }
            BenefitAudit::record('benefit.schedule_funded', $schedule, ['amount' => $amount, 'available_funds' => $funds]);
        });
    }

    public function cancel(BenefitSchedule $schedule): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        DB::transaction(function () use ($schedule): void {
            $schedule = BenefitSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            abort_unless($schedule->status === 'DRAFT', 409);
            $schedule->entitlements()->update(['benefit_schedule_id' => null, 'status' => BenefitStatus::Pending->value, 'reviewed_at' => null, 'reviewed_by' => null]);
            $schedule->recipients()->update(['outcome' => 'PENDING']);
            $schedule->update(['status' => 'CANCELLED']);
            BenefitAudit::record('benefit.schedule_cancelled', $schedule);
        });
    }

    public function close(BenefitSchedule $schedule): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        DB::transaction(function () use ($schedule): void {
            $schedule = BenefitSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            abort_unless($schedule->status === 'APPROVED' && $schedule->scheduled_at->lte(now()), 409, 'Close an approved schedule after its payout date.');
            $schedule->entitlements()->where('status', BenefitStatus::Unpaid)->update(['status' => BenefitStatus::Unclaimed->value]);
            $schedule->recipients()->whereHas('entitlement', fn ($query) => $query->where('status', BenefitStatus::Unclaimed))->update(['outcome' => 'UNCLAIMED']);
            $schedule->update(['status' => 'CLOSED', 'closed_at' => now()]);
            BenefitAudit::record('benefit.schedule_closed', $schedule);
        });
    }

    public function reschedule(BenefitEntitlement $entitlement, BenefitSchedule $target): void
    {
        Gate::authorize('manage', BenefitSchedule::class);
        DB::transaction(function () use ($entitlement, $target): void {
            $scheduleIds = array_filter([$entitlement->benefit_schedule_id, $target->id]);
            $schedules = BenefitSchedule::query()->whereIn('id', $scheduleIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $target = $schedules->get($target->id);
            $entitlement = BenefitEntitlement::query()->lockForUpdate()->findOrFail($entitlement->id);
            abort_unless($target->status === 'DRAFT' && $target->program_id === $entitlement->program_id, 422, 'Use a draft schedule for the same program.');
            abort_if($entitlement->status === BenefitStatus::Claimed, 409, 'Claimed benefits cannot be rescheduled.');
            if ($entitlement->benefit_schedule_id) {
                abort_unless($schedules->get($entitlement->benefit_schedule_id)?->status === 'CLOSED', 409, 'Close the previous schedule before rescheduling.');
            }
            $senior = $entitlement->seniorCitizen;
            abort_if($target->barangay_ids && ! in_array($senior->barangay_id, $target->barangay_ids), 422, 'The barangay is outside this batch.');
            if ($reason = $this->eligibility->reason($senior, $entitlement->rule_snapshot, $entitlement->eligibility_on, $entitlement->milestone_age)) {
                throw ValidationException::withMessages(['reschedule' => $reason]);
            }
            $entitlement->update(['benefit_schedule_id' => $target->id, 'status' => BenefitStatus::Pending, 'reviewed_at' => null, 'reviewed_by' => null, 'amount' => $entitlement->amount ?? $target->amount]);
            BenefitScheduleRecipient::firstOrCreate(['benefit_schedule_id' => $target->id, 'benefit_entitlement_id' => $entitlement->id]);
            BenefitAudit::record('benefit.rescheduled', $entitlement, ['schedule_id' => $target->id, 'original_period' => $entitlement->period_key]);
        });
    }
}
