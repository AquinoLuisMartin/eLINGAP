<?php

namespace Database\Seeders;

use App\Models\BenefitEntitlement;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Services\Benefits\BenefitAudit;
use App\Services\Benefits\BenefitEligibility;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EligibleBenefitEntitlementSeeder extends Seeder
{
    public function run(BenefitEligibility $eligibility): void
    {
        $on = today();
        Program::query()->whereNotNull('benefit_code')->whereNotNull('benefit_rules')
            ->whereIn('status', ['ACTIVE', 'UPCOMING'])->orderBy('id')->each(function (Program $program) use ($eligibility, $on): void {
                $rules = $program->benefit_rules;
                $cycle = match ($rules['cadence'] ?? null) {
                    'ANNUAL', 'MILESTONE' => (string) $on->year,
                    'QUARTERLY' => $on->year.'-Q'.$on->quarter,
                    'SEMIANNUAL' => $on->year.'-H'.($on->month <= 6 ? 1 : 2),
                    default => null,
                };
                if ($cycle === null) {
                    $this->command?->line($program->name.': skipped; on-demand cases require an office-assigned period.');

                    return;
                }
                $periods = $eligibility->periods($rules, $on, $cycle);
                $snapshot = [...$rules, 'government_level' => $program->government_level, 'program_name' => $program->name];
                $created = DB::transaction(function () use ($program, $eligibility, $on, $periods, $rules, $snapshot): int {
                    $created = 0;
                    SeniorCitizen::query()->where('status', 'VERIFIED')->whereNotNull('verified_at')
                        ->whereNotNull('osca_id_number')->whereNotNull('id_generated_at')
                        ->whereNull('died_on')->whereNull('death_declared_at')
                        ->select(['id', 'status', 'verified_at', 'osca_id_number', 'id_generated_at', 'birth_date',
                            'classification_verified_at', 'is_bedridden', 'is_indigent', 'has_national_pension', 'has_local_pension',
                            'tss_verified_at', 'tss_request'])
                        ->chunkById(200, function ($seniors) use ($program, $eligibility, $on, $periods, $rules, $snapshot, &$created): void {
                            $rows = [];
                            foreach ($seniors as $senior) {
                                foreach ($periods as $period) {
                                    $date = $eligibility->periodDate($rules, $on, $period['key']);
                                    if ($eligibility->reason($senior, $rules, $date, $period['age']) !== null) {
                                        continue;
                                    }
                                    $rows[] = [
                                        'program_id' => $program->id, 'senior_citizen_id' => $senior->id,
                                        'period_key' => $period['key'], 'milestone_age' => $period['age'],
                                        'eligibility_on' => $date->toDateString(), 'status' => 'PENDING',
                                        'amount' => $period['age'] !== null ? $rules['milestones'][$period['age']] : $program->default_amount,
                                        'rule_snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                                        'created_at' => now(), 'updated_at' => now(),
                                    ];
                                }
                            }
                            if ($rows) {
                                $created += BenefitEntitlement::query()->insertOrIgnore($rows);
                            }
                        });
                    if ($created > 0) {
                        BenefitAudit::record('benefit.eligible_recipients_seeded', $program, ['created' => $created, 'eligibility_on' => $on->toDateString()]);
                    }

                    return $created;
                });
                $this->command?->line($program->name.': '.$created.' pending entitlements created.');
            });
    }
}
