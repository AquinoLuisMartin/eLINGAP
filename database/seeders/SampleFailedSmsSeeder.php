<?php

namespace Database\Seeders;

use App\Enums\SmsStatus;
use App\Models\SmsBlast;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SampleFailedSmsSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Sample failed SMS data is only available outside production.');
        }

        $this->call(SampleSmsBroadcastSeeder::class);

        DB::transaction(function (): void {
            $reasons = ['Invalid number format', 'Carrier network timeout', 'Inbox full', 'SMS Gateway error'];
            SmsBlast::query()->where('message', 'like', '[MOCK BROADCAST]%')->orderBy('id')->limit(3)
                ->each(function (SmsBlast $blast) use ($reasons): void {
                    $messages = $blast->messages()->where('provider_message_id', 'like', 'MOCK-BROADCAST-%')
                        ->whereHas('seniorCitizen', fn ($query) => $query->where('address', 'SYNTHETIC ADDRESS - NOT A RESIDENCE'))
                        ->orderBy('id')->limit(4)->get();
                    foreach ($messages as $index => $message) {
                        if ($message->deliveryLogs()->where('status', SmsStatus::Failed->value)->exists()) {
                            continue;
                        }
                        $message->update([
                            'status' => SmsStatus::Failed, 'failure_reason' => $reasons[$index], 'sent_at' => null,
                            'recipient_number' => '09XX XXX '.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                        ]);
                        $message->deliveryLogs()->create([
                            'status' => SmsStatus::Failed, 'provider_message_id' => $message->provider_message_id,
                            'response_payload' => ['mock' => true, 'error' => $reasons[$index], 'sms_sent' => false], 'recorded_at' => now(),
                        ]);
                    }
                });
        });

        $this->command?->info('Sample failed notifications are ready. Resend simulates success; no SMS is sent.');
    }
}
