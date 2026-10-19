<?php

namespace App\Services\Sms;

use App\Enums\SmsStatus;
use App\Jobs\Sms\SendSmsJob;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\DB;

class SmsService
{
    public function queue(string $recipientNumber, string $message, ?int $userId = null, ?int $seniorCitizenId = null, ?int $blastId = null, ?int $entitlementId = null): SmsMessage
    {
        return DB::transaction(function () use ($recipientNumber, $message, $userId, $seniorCitizenId, $blastId, $entitlementId): SmsMessage {
            $smsMessage = SmsMessage::create([
                'recipient_number' => $recipientNumber,
                'message' => $message,
                'created_by' => $userId,
                'senior_citizen_id' => $seniorCitizenId,
                'sms_blast_id' => $blastId,
                'benefit_entitlement_id' => $entitlementId,
                'status' => SmsStatus::Queued,
                'queued_at' => now(),
            ]);

            SendSmsJob::dispatch($smsMessage->id)->onConnection('database-atomic')->beforeCommit();

            return $smsMessage;
        });
    }
}
