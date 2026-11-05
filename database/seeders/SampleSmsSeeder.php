<?php

namespace Database\Seeders;

use App\Enums\SmsStatus;
use App\Models\SeniorCitizen;
use App\Models\SmsMessage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SampleSmsSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Sample SMS data is only available outside production.');
        }

        $seniorIds = SeniorCitizen::query()
            ->where('address', 'SYNTHETIC ADDRESS - NOT A RESIDENCE')
            ->whereHas('beneficiaries', fn ($query) => $query->where('status', 'ACTIVE'))
            ->orderBy('id')->limit(12)->pluck('id');

        if ($seniorIds->isEmpty()) {
            $this->command?->warn('No synthetic beneficiaries found. Load the sample registry first.');

            return;
        }

        DB::transaction(function () use ($seniorIds): void {
            $examples = [
                '[MOCK SMS] OSCA: Your benefit payout schedule is ready. Please bring your senior citizen ID. Sample notification only.',
                '[MOCK SMS] OSCA: Your benefit application has been approved. Please visit the office for the next steps. Sample notification only.',
                '[MOCK SMS] OSCA: Reminder to confirm your attendance for the upcoming beneficiary orientation. Sample notification only.',
            ];

            foreach ($seniorIds as $index => $seniorId) {
                $sentAt = now()->subHours($index + 1);
                $message = SmsMessage::firstOrCreate(['provider_message_id' => 'MOCK-SMS-'.$seniorId], [
                    'senior_citizen_id' => $seniorId,
                    'recipient_number' => 'MOCK-RECIPIENT-'.($index + 1),
                    'message' => $examples[$index % count($examples)],
                    'status' => SmsStatus::Sent,
                    'queued_at' => $sentAt->copy()->subMinute(),
                    'sent_at' => $sentAt,
                ]);
                $message->deliveryLogs()->firstOrCreate(['status' => SmsStatus::Sent->value], [
                    'provider_message_id' => $message->provider_message_id,
                    'response_payload' => ['mock' => true, 'status' => 'simulated', 'sms_sent' => false],
                    'recorded_at' => $message->sent_at,
                ]);
            }
        });

        $this->command?->info('Sample SMS history is ready for '.$seniorIds->count().' synthetic beneficiaries. No SMS was sent.');
    }
}
