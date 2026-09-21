<?php

namespace App\Services\Sms;

use App\Enums\SmsStatus;
use App\Jobs\Sms\SendSmsJob;
use App\Models\SmsMessage;

class SmsService
{
    public function queue(string $recipientNumber, string $message, ?int $userId = null, ?int $seniorCitizenId = null): SmsMessage
    {
        $smsMessage = SmsMessage::create([
            'recipient_number' => $recipientNumber,
            'message' => $message,
            'created_by' => $userId,
            'senior_citizen_id' => $seniorCitizenId,
            'status' => SmsStatus::Queued,
            'queued_at' => now(),
        ]);

        SendSmsJob::dispatch($smsMessage->id)->afterCommit();

        return $smsMessage;
    }
}
