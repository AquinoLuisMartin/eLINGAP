<?php

namespace App\Services\Benefits;

use App\Enums\BenefitStatus;
use App\Models\BenefitEntitlement;
use App\Models\BenefitSchedule;
use App\Models\SmsBlast;
use App\Services\Sms\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class BenefitSms
{
    public function template(BenefitSchedule $schedule): string
    {
        $schedule->loadMissing('program');
        $name = $schedule->rule_snapshot['program_name'] ?? $schedule->program->name;

        return "Office of the Senior Citizen Affairs\nMunicipality of Sta. Maria, Bulacan\n\nProgram: {$name}\nBenefit period: [PERIOD]\nAmount: PHP [AMOUNT]\nPayout: {$schedule->scheduled_at->format('M j, Y g:i A')}\nLocation: {$schedule->location}"
            .($schedule->requirements ? "\nRequirements: {$schedule->requirements}" : '')
            .($schedule->allow_representative ? "\nAn authorized representative may claim." : "\nPersonal claiming only.");
    }

    public function eligible(?BenefitEntitlement $entry, ?int $scheduleId = null): bool
    {
        if (! $entry) {
            return false;
        }
        $entry->loadMissing(['seniorCitizen', 'schedule.program']);
        if ($scheduleId !== null && $entry->benefit_schedule_id !== $scheduleId) {
            return false;
        }
        if ($entry->status !== BenefitStatus::Unpaid || $entry->schedule?->status !== 'APPROVED' || $entry->schedule->program->status !== 'ACTIVE') {
            return false;
        }
        $on = isset($entry->rule_snapshot['claim_window_months']) ? now()->startOfDay() : $entry->eligibility_on;

        return app(BenefitEligibility::class)->reason($entry->seniorCitizen, $entry->rule_snapshot, $on, $entry->milestone_age) === null;
    }

    private function entries(BenefitSchedule $schedule, ?int $barangay)
    {
        return $schedule->entitlements()->where('status', BenefitStatus::Unpaid)->with(['seniorCitizen', 'schedule.program'])
            ->when($barangay, fn ($query) => $query->whereHas('seniorCitizen', fn ($query) => $query->where('barangay_id', $barangay)));
    }

    public function preview(BenefitSchedule $schedule, string $message, ?int $barangay): array
    {
        Gate::authorize('manage', BenefitSchedule::class);
        abort_unless($schedule->status === 'APPROVED', 422, 'Approve the recipient list before previewing payout SMS.');
        $context = hash_init('sha256');
        hash_update($context, json_encode([$schedule->id, $schedule->updated_at, $message, $barangay]));
        $eligible = 0;
        $excluded = 0;
        $example = '';
        $this->entries($schedule, $barangay)->chunkById(200, function ($entries) use (&$eligible, &$excluded, &$example, $context, $message): void {
            foreach ($entries as $entry) {
                if (! $this->eligible($entry) || ! $entry->seniorCitizen->smsContact()) {
                    $excluded++;

                    continue;
                }
                $eligible++;
                $text = $this->personalize($message, $entry);
                validator(['sms' => $text], ['sms' => ['required', 'string', 'max:1600']])->validate();
                $example = $example ?: $text;
                hash_update($context, json_encode([$entry->id, $entry->amount, $entry->period_key, $entry->seniorCitizen->smsContact(), $text]));
            }
        });

        return ['hash' => hash_final($context), 'eligible' => $eligible, 'excluded' => $excluded, 'example' => $example];
    }

    public function send(BenefitSchedule $schedule, string $message, ?int $barangay, string $previewHash): SmsBlast
    {
        Gate::authorize('manage', BenefitSchedule::class);

        return DB::transaction(function () use ($schedule, $message, $barangay, $previewHash) {
            $schedule = BenefitSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            $preview = $this->preview($schedule, $message, $barangay);
            if (! hash_equals($preview['hash'], $previewHash) || ! $preview['eligible']) {
                throw ValidationException::withMessages(['sms' => 'Recipients or message changed. Preview and approve the message again.']);
            }
            if (SmsBlast::where('approval_hash', $previewHash)->exists()) {
                throw ValidationException::withMessages(['sms' => 'This approved message was already queued. Retry failed deliveries from the SMS log.']);
            }
            $blast = SmsBlast::create(['created_by' => auth()->id(), 'barangay_id' => $barangay, 'benefit_schedule_id' => $schedule->id, 'approval_hash' => $previewHash, 'message' => $message, 'recipient_count' => $preview['eligible'], 'excluded_count' => $preview['excluded']]);
            $this->entries($schedule, $barangay)->chunkById(200, function ($entries) use ($blast, $message): void {
                $messages = [];
                foreach ($entries as $entry) {
                    if ($this->eligible($entry) && $number = $entry->seniorCitizen->smsContact()) {
                        $messages[] = ['recipient_number' => $number, 'message' => $this->personalize($message, $entry), 'created_by' => auth()->id(),
                            'senior_citizen_id' => $entry->senior_citizen_id, 'sms_blast_id' => $blast->id, 'benefit_entitlement_id' => $entry->id];
                    }
                }
                app(SmsService::class)->queueMany($messages);
            });
            BenefitAudit::record('benefit.sms_approved', $blast, ['schedule_id' => $schedule->id, 'recipients' => $preview['eligible']]);

            return $blast;
        });
    }

    private function personalize(string $message, BenefitEntitlement $entry): string
    {
        return str_replace(['[PERIOD]', '[AMOUNT]'], [$entry->period_key, number_format((float) $entry->amount, 2)], $message);
    }
}
