<?php

namespace App\Jobs\Sms;

use App\Enums\SmsStatus;
use App\Models\SmsMessage;
use App\Services\Benefits\BenefitSms;
use App\Services\Sms\SmsGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [5, 30];

    public function __construct(public readonly int $smsMessageId) {}

    public function handle(SmsGateway $gateway): void
    {
        $smsMessage = SmsMessage::query()->findOrFail($this->smsMessageId);
        if ($smsMessage->status !== SmsStatus::Queued) {
            return;
        }

        if ($smsMessage->senior_citizen_id) {
            $senior = $smsMessage->seniorCitizen;
            if ($senior?->status?->value !== 'VERIFIED' || $senior->smsContact() !== $smsMessage->recipient_number || ($smsMessage->benefit_entitlement_id && ! app(BenefitSms::class)->eligible($smsMessage->benefitEntitlement, $smsMessage->blast?->benefit_schedule_id))) {
                $smsMessage->update(['status' => SmsStatus::Failed, 'failure_reason' => 'Recipient is no longer eligible.']);
                $smsMessage->deliveryLogs()->create(['status' => SmsStatus::Failed, 'response_payload' => ['error' => 'Recipient is no longer eligible.'], 'recorded_at' => now()]);

                return;
            }
        }

        $result = $gateway->send($smsMessage->recipient_number, $smsMessage->message, 'sms-'.$smsMessage->id);
        $smsMessage->update(['status' => SmsStatus::Sent, 'provider_message_id' => $result['provider_message_id'], 'sent_at' => now()]);
        $smsMessage->deliveryLogs()->create(['status' => SmsStatus::Sent, 'provider_message_id' => $result['provider_message_id'], 'response_payload' => $result['response'], 'recorded_at' => now()]);
    }

    public function failed(?Throwable $exception): void
    {
        $smsMessage = SmsMessage::query()->find($this->smsMessageId);
        if (! $smsMessage) {
            return;
        }

        $reason = 'SMS delivery failed. Check the provider configuration and delivery status before retrying.';
        $smsMessage->update(['status' => SmsStatus::Failed, 'failure_reason' => $reason]);
        $smsMessage->deliveryLogs()->create(['status' => SmsStatus::Failed, 'response_payload' => ['error' => $reason], 'recorded_at' => now()]);
    }
}
