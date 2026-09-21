<?php

namespace App\Jobs\Sms;

use App\Enums\SmsStatus;
use App\Models\SmsMessage;
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

    public array $backoff = [5, 30];

    public function __construct(public readonly int $smsMessageId) {}

    public function handle(SmsGateway $gateway): void
    {
        $smsMessage = SmsMessage::query()->findOrFail($this->smsMessageId);
        if ($smsMessage->status !== SmsStatus::Queued) {
            return;
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

        $smsMessage->update(['status' => SmsStatus::Failed, 'failure_reason' => $exception?->getMessage()]);
        $smsMessage->deliveryLogs()->create(['status' => SmsStatus::Failed, 'response_payload' => ['error' => $exception?->getMessage()], 'recorded_at' => now()]);
    }
}
