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
                    $purpose = match ($program->benefit_code) {
                        'december-assistance' => 'Pamamahagi ng taunang tulong pinansyal sa mga nakumpirmang benepisyaryo ngayong Disyembre.',
                        'tss' => 'Pagsusuri ng inyong kahilingan para sa tulong pinansyal.',
                        'national-milestones' => 'Pagpapatunay ng edad at pagproseso ng cash gift para sa mga kwalipikadong senior.',
                        'lgu-age-90' => 'Pagpapatunay ng edad at pagproseso ng cash gift para sa mga kwalipikadong 90 taong gulang.',
                        'lgu-age-100' => 'Pagpapatunay ng edad at pagproseso ng cash gift para sa mga kwalipikadong 100 taong gulang.',
                        'provincial-centenarian' => 'Pagpapatunay para sa cash gift at grocery assistance ng mga kwalipikadong centenarian.',
                        'national-social-pension' => 'Pagpapatunay ng kwalipikasyon at talaan para sa payout ng pambansang social pension.',
                        'local-social-pension' => 'Pagpapatunay ng kwalipikasyon at talaan para sa payout ng lokal na social pension.',
                        'october-assistance' => 'Pagpapatunay ng kwalipikasyon para sa tulong sa mga nakaratay at indigent na senior.',
                        default => 'Pagpapatunay ng kwalipikasyon at pagproseso ng tulong para sa mga benepisyaryo.',
                    };
                    $documents = match ($program->benefit_code) {
                        'tss' => ' at mga dokumentong sumusuporta sa kahilingan',
                        'national-milestones', 'lgu-age-90', 'lgu-age-100', 'provincial-centenarian' => ' at patunay ng kapanganakan',
                        'october-assistance' => ' at mga dokumentong nagpapatunay ng kalagayan',
                        default => '',
                    };
                    $name = Str::limit($program->name, 120, '');
                    $body = "OSCA | {$program->name}\n{$purpose}\nPetsa: [Petsa]\nOras: [Oras]\nLugar: [Lugar/Venue]\nDalhin: OSCA ID o valid ID{$documents}. Kung pinapayagan ang kinatawan, kailangan ang authorization letter at valid ID ng kinatawan.\nDumating sa oras. Walang bayad; huwag magbigay ng pera o personal na impormasyon sa hindi awtorisadong tao.";
                    $template = SmsTemplate::query()->where('program_id', $program->id)->first()
                        ?? SmsTemplate::query()->whereNull('program_id')->whereNull('superseded_by_id')->where('name', $name)->first()
                        ?? new SmsTemplate(['name' => $name, 'body' => $body, 'is_active' => true]);
                    $template->fill(['program_id' => $program->id])->save();
                    SmsTemplate::query()->whereKeyNot($template->id)->whereIn('name', [Str::limit($program->name, 85).' - Iskedyul', Str::limit($program->name, 85).' - Mga kinakailangan'])
                        ->update(['is_active' => false, 'superseded_by_id' => $template->id]);
                });
        });

        $this->command?->info('One complete SMS template per program is ready. Superseded fragments and message history are retained.');
    }
}
