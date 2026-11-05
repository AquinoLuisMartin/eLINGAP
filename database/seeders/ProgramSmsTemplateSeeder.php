<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProgramSmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            Program::query()->select(['id', 'name', 'benefit_code'])->whereIn('status', ['ACTIVE', 'UPCOMING'])
                ->orderBy('id')->each(function (Program $program): void {
                    $guidance = match ($program->benefit_code) {
                        'tss' => 'Makipag-ugnayan sa OSCA para sa pagsusuri ng inyong kahilingan at kaukulang dokumento.',
                        'national-milestones', 'lgu-age-90', 'lgu-age-100', 'provincial-centenarian' => 'Makipag-ugnayan sa OSCA para sa pagpapatunay ng edad at mga dokumentong kailangan sa aplikasyon.',
                        'national-social-pension', 'local-social-pension' => 'Makipag-ugnayan sa OSCA para sa pagpapatunay ng kwalipikasyon at katayuan sa listahan ng pensyon.',
                        default => 'Makipag-ugnayan sa OSCA para sa kwalipikasyon at mga dokumentong kailangan sa aplikasyon.',
                    };

                    foreach ([
                        'Mga kinakailangan' => "OSCA: Paalala tungkol sa {$program->name}. {$guidance} Ihanda ang inyong senior citizen ID.",
                        'Iskedyul' => "OSCA: Para sa mga benepisyaryo ng {$program->name}, kumpirmahin sa tanggapan ang opisyal na petsa, oras, lugar at mga kailangang dalhin bago pumunta. Maghintay ng kumpirmasyon mula sa OSCA.",
                    ] as $purpose => $body) {
                        SmsTemplate::firstOrCreate(['name' => Str::limit($program->name, 85).' - '.$purpose], [
                            'body' => $body, 'is_active' => true,
                        ]);
                    }
                });
        });

        $this->command?->info('SMS templates are ready for existing active and upcoming programs. Existing templates were preserved.');
    }
}
