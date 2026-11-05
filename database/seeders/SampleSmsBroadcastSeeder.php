<?php

namespace Database\Seeders;

use App\Enums\SmsStatus;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\SmsBlast;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SampleSmsBroadcastSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Sample SMS broadcasts are only available outside production.');
        }

        $senderId = User::query()->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('name', 'OSCA_STAFF'))->orderBy('id')->value('id');
        if (! $senderId) {
            $this->command?->warn('An active OSCA staff account is required for sample broadcast history.');

            return;
        }

        $programs = Program::query()->select(['id', 'name'])->whereIn('status', ['ACTIVE', 'UPCOMING'])
            ->whereHas('beneficiaries', fn ($query) => $query->where('status', 'ACTIVE')
                ->whereHas('seniorCitizen', fn ($query) => $query->where('address', 'SYNTHETIC ADDRESS - NOT A RESIDENCE')))
            ->orderBy('id')->limit(3)->get();

        DB::transaction(function () use ($programs, $senderId): void {
            foreach ($programs as $index => $program) {
                $seniorIds = SeniorCitizen::query()->where('address', 'SYNTHETIC ADDRESS - NOT A RESIDENCE')
                    ->whereHas('beneficiaries', fn ($query) => $query->where('program_id', $program->id)->where('status', 'ACTIVE'))
                    ->orderBy('id')->limit(12)->pluck('id');
                $sentAt = now()->subDays($index + 1);
                $blast = SmsBlast::firstOrCreate([
                    'message' => "[MOCK BROADCAST] OSCA: Paalala para sa mga benepisyaryo ng {$program->name}. Kumpirmahin sa OSCA ang opisyal na iskedyul at mga kailangang dalhin. Sample notification only; no SMS was sent.",
                ], ['created_by' => $senderId, 'recipient_count' => $seniorIds->count(), 'excluded_count' => 0,
                    'created_at' => $sentAt, 'updated_at' => $sentAt]);

                foreach ($seniorIds as $seniorId) {
                    $message = $blast->messages()->firstOrCreate(['provider_message_id' => 'MOCK-BROADCAST-'.$program->id.'-'.$seniorId], [
                        'senior_citizen_id' => $seniorId, 'created_by' => $senderId,
                        'recipient_number' => 'MOCK-RECIPIENT-'.$seniorId, 'message' => $blast->message,
                        'status' => SmsStatus::Sent, 'queued_at' => $sentAt->copy()->subMinute(), 'sent_at' => $sentAt,
                    ]);
                    $message->deliveryLogs()->firstOrCreate(['status' => SmsStatus::Sent->value], [
                        'provider_message_id' => $message->provider_message_id, 'recorded_at' => $message->sent_at,
                        'response_payload' => ['mock' => true, 'status' => 'simulated', 'sms_sent' => false],
                    ]);
                }
                $blast->update(['recipient_count' => $blast->messages()->count()]);
            }
        });

        $this->command?->info('Sample broadcast history is ready for '.$programs->count().' programs. No SMS was sent.');
    }
}
