<?php

namespace App\Services\Benefits;

use App\Enums\BenefitStatus;
use App\Models\BenefitEntitlement;
use App\Models\BenefitSchedule;
use App\Models\SmsBlast;
use App\Services\OfficeSettings;
use App\Services\Sms\SmsService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class BenefitSms
{
    public function __construct(private OfficeSettings $settings) {}

    public function template(BenefitSchedule $schedule): string
    {
        $schedule->loadMissing('program');
        $name = $schedule->rule_snapshot['program_name'] ?? $schedule->program->name;
        $office = $this->settings->values();
        $heading = implode("\n", array_filter([$office['office_name'], $office['organization_name']]));

        return ($heading === '' ? '' : $heading."\n\n")."Program: {$name}\nBenefit period: [PERIOD]\nAmount: PHP [AMOUNT]\nPayout: {$schedule->payoutWindow()}\nLocation: {$schedule->location}"
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

    private function entries(BenefitSchedule $schedule, ?int $barangay): HasMany
    {
        return $schedule->entitlements()->where('status', BenefitStatus::Unpaid)->with(['seniorCitizen', 'schedule.program'])
            ->when($barangay, fn ($query) => $query->whereHas('seniorCitizen', fn ($query) => $query->where('barangay_id', $barangay)));
    }

    public function preview(BenefitSchedule $schedule, string $message, ?int $barangay): array
    {
        Gate::authorize('manage', BenefitSchedule::class);

        return $this->reviewAudience($schedule, $message, $barangay)['preview'];
    }

    private function reviewAudience(BenefitSchedule $schedule, string $message, ?int $barangay, bool $prepareMessages = false): array
    {
        abort_unless($schedule->status === 'APPROVED', 422, 'Approve the recipient list before previewing payout SMS.');
        $context = hash_init('sha256');
        hash_update($context, json_encode([$schedule->id, $schedule->updated_at, $message, $barangay]));
        $eligible = 0;
        $excluded = 0;
        $alreadyNotified = 0;
        $example = '';
        $messages = [];
        $this->entries($schedule, $barangay)->withExists('activePayoutNotifications as already_notified')
            ->withMax('payoutNotifications as last_payout_message_id', 'id')->chunkById(200, function ($entries) use (&$eligible, &$excluded, &$alreadyNotified, &$example, &$messages, $context, $message, $prepareMessages): void {
                foreach ($entries as $entry) {
                    if ($entry->already_notified) {
                        $alreadyNotified++;
                        $excluded++;

                        continue;
                    }
                    if (! $this->eligible($entry) || ! ($number = $entry->seniorCitizen->smsContact())) {
                        $excluded++;

                        continue;
                    }
                    $eligible++;
                    $text = $this->personalize($message, $entry);
                    validator(['sms' => $text], ['sms' => ['required', 'string', 'max:1600']])->validate();
                    $example = $example ?: $text;
                    hash_update($context, json_encode([$entry->id, $entry->amount, $entry->period_key, $entry->last_payout_message_id, $number, $text]));
                    if ($prepareMessages) {
                        $messages[] = ['recipient_number' => $number, 'message' => $text, 'created_by' => auth()->id(),
                            'senior_citizen_id' => $entry->senior_citizen_id, 'benefit_entitlement_id' => $entry->id];
                    }
                }
            });

        return ['preview' => ['hash' => hash_final($context), 'eligible' => $eligible, 'excluded' => $excluded,
            'already_notified' => $alreadyNotified, 'example' => $example], 'messages' => $messages];
    }

    public function send(BenefitSchedule $schedule, string $message, ?int $barangay, string $previewHash): SmsBlast
    {
        Gate::authorize('manage', BenefitSchedule::class);

        return DB::transaction(function () use ($schedule, $message, $barangay, $previewHash) {
            $schedule = BenefitSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            // The entitlement row is shared across schedules and locked by both batch sends and retries.
            $this->entries($schedule, $barangay)->toBase()->select('benefit_entitlements.id')->orderBy('benefit_entitlements.id')->lockForUpdate()->get();
            ['preview' => $preview, 'messages' => $messages] = $this->reviewAudience($schedule, $message, $barangay, prepareMessages: true);
            if (! hash_equals($preview['hash'], $previewHash) || ! $preview['eligible']) {
                throw ValidationException::withMessages(['sms' => 'Recipients or message changed. Preview and approve the message again.']);
            }
            if (SmsBlast::where('approval_hash', $previewHash)->exists()) {
                throw ValidationException::withMessages(['sms' => 'This approved message was already queued. Retry failed deliveries from the SMS log.']);
            }
            $blast = SmsBlast::create(['created_by' => auth()->id(), 'barangay_id' => $barangay, 'benefit_schedule_id' => $schedule->id, 'approval_hash' => $previewHash, 'message' => $message, 'recipient_count' => $preview['eligible'], 'excluded_count' => $preview['excluded']]);
            app(SmsService::class)->queueMany(array_map(fn (array $attributes) => $attributes + ['sms_blast_id' => $blast->id], $messages));
            BenefitAudit::record('benefit.sms_approved', $blast, ['schedule_id' => $schedule->id, 'recipients' => $preview['eligible']]);

            return $blast;
        });
    }

    private function personalize(string $message, BenefitEntitlement $entry): string
    {
        return str_replace(['[PERIOD]', '[AMOUNT]'], [$entry->period_key, number_format((float) $entry->amount, 2)], $message);
    }
}
