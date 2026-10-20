<?php

namespace App\Services\Sms;

use App\Enums\SmsStatus;
use App\Jobs\Sms\SendSmsJob;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class SmsService
{
    public function queueMany(array $messages): void
    {
        DB::transaction(function () use ($messages): void {
            foreach (array_chunk($messages, 200) as $chunk) {
                $bindings = [];
                $now = now()->toDateTimeString();
                foreach ($chunk as $attributes) {
                    array_push($bindings, $attributes['recipient_number'], $attributes['message'], $attributes['created_by'] ?? null,
                        $attributes['senior_citizen_id'] ?? null, $attributes['sms_blast_id'] ?? null, $attributes['benefit_entitlement_id'] ?? null,
                        SmsStatus::Queued->value, $now, $now, $now);
                }
                // PostgreSQL returns generated identifiers without reloading recipient data.
                $inserted = DB::select('INSERT INTO sms_messages (recipient_number, message, created_by, senior_citizen_id, sms_blast_id, benefit_entitlement_id, status, queued_at, created_at, updated_at) VALUES '
                    .implode(', ', array_fill(0, count($chunk), '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')).' RETURNING id', $bindings, false);
                $jobs = array_map(fn ($message) => (new SendSmsJob($message->id))->onConnection('database-atomic')->beforeCommit(), $inserted);
                Queue::connection('database-atomic')->bulk($jobs);
            }
        });
    }

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
