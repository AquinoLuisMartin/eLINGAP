<?php

namespace Tests\Feature\Benefits;

use App\Models\Barangay;
use App\Models\BenefitEntitlement;
use App\Models\Program;
use App\Models\SeniorCitizen;
use Database\Seeders\BenefitProgramSeeder;
use Database\Seeders\EligibleBenefitEntitlementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class EligibleBenefitEntitlementSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_current_periods_and_only_eligible_milestones_without_approving_payments(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 30)->startOfDay());
        $this->seed(BenefitProgramSeeder::class);
        $senior = $this->senior();

        $this->seed(EligibleBenefitEntitlementSeeder::class);

        $this->assertDatabaseCount('benefit_entitlements', 5);
        foreach ([
            ['december-assistance', '2026', 500],
            ['lgu-age-90', 'AGE-90', 5000],
            ['national-social-pension', '2026-Q4', null],
            ['local-social-pension', '2026-H2', 500],
            ['national-milestones', 'AGE-90', 10000],
        ] as [$code, $period, $amount]) {
            $this->assertDatabaseHas('benefit_entitlements', [
                'program_id' => Program::where('benefit_code', $code)->value('id'), 'senior_citizen_id' => $senior->id,
                'period_key' => $period, 'amount' => $amount, 'status' => 'PENDING', 'reviewed_at' => null, 'benefit_schedule_id' => null,
            ]);
        }
        $this->assertDatabaseCount('benefit_schedules', 0);
        $this->assertDatabaseCount('benefit_payments', 0);
        $this->assertDatabaseCount('payouts', 0);
        $this->assertDatabaseCount('beneficiaries', 0);
        $this->assertDatabaseHas('programs', ['benefit_code' => 'lgu-age-90', 'status' => 'UPCOMING']);
    }

    #[TestWith(['verified_at', null])]
    #[TestWith(['id_generated_at', null])]
    #[TestWith(['osca_id_number', null])]
    #[TestWith(['birth_date', '2000-01-01'])]
    #[TestWith(['status', 'DECEASED'])]
    #[TestWith(['died_on', '2026-10-01'])]
    #[TestWith(['death_declared_at', '2026-10-01'])]
    #[TestWith(['verified_at', '2026-10-31'])]
    public function test_does_not_seed_unverified_unissued_underage_or_deceased_records(string $field, mixed $value): void
    {
        $this->travelTo(now()->setDate(2026, 10, 30)->startOfDay());
        $this->seed(BenefitProgramSeeder::class);
        $this->senior([$field => $value]);

        $this->seed(EligibleBenefitEntitlementSeeder::class);

        $this->assertDatabaseCount('benefit_entitlements', 0);
    }

    public function test_reseeding_preserves_claims_amounts_and_rule_snapshots(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 30)->startOfDay());
        $this->seed(BenefitProgramSeeder::class);
        $this->senior();
        $this->seed(EligibleBenefitEntitlementSeeder::class);
        $entry = BenefitEntitlement::where('period_key', '2026')->sole();
        $entry->update(['status' => 'CLAIMED', 'amount' => '750.00', 'claimed_at' => now()]);
        $entry->program->update(['default_amount' => '999.00', 'benefit_rules' => ['cadence' => 'ANNUAL']]);

        $this->seed(EligibleBenefitEntitlementSeeder::class);

        $this->assertDatabaseCount('benefit_entitlements', 5);
        $this->assertDatabaseHas('benefit_entitlements', ['id' => $entry->id, 'status' => 'CLAIMED', 'amount' => 750]);
        $this->assertSame('06-30', $entry->fresh()->rule_snapshot['cutoff']);
    }

    public function test_enforces_cutoff_and_classifications_without_inventing_office_case_periods(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 30)->startOfDay());
        $this->seed(BenefitProgramSeeder::class);
        $senior = $this->senior(['verified_at' => '2026-07-01', 'classification_verified_at' => '2026-07-01',
            'is_bedridden' => true, 'is_indigent' => true, 'tss_verified_at' => '2026-07-01', 'tss_request' => 'Synthetic office request']);

        $this->seed(EligibleBenefitEntitlementSeeder::class);

        $this->assertDatabaseMissing('benefit_entitlements', ['program_id' => Program::where('benefit_code', 'december-assistance')->value('id')]);
        $this->assertDatabaseHas('benefit_entitlements', ['program_id' => Program::where('benefit_code', 'october-assistance')->value('id'), 'senior_citizen_id' => $senior->id, 'amount' => null]);
        $this->assertDatabaseMissing('benefit_entitlements', ['program_id' => Program::where('benefit_code', 'tss')->value('id')]);
    }

    private function senior(array $attributes = []): SeniorCitizen
    {
        $barangay = Barangay::create(['name' => 'Synthetic area', 'code' => 'SYN-SEED']);

        return SeniorCitizen::forceCreate([...[
            'barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => 'Recipient',
            'birth_date' => '1936-10-01', 'sex' => 'FEMALE', 'address' => 'Synthetic address',
            'status' => 'VERIFIED', 'verified_at' => '2026-01-01', 'id_generated_at' => '2026-01-02',
            'osca_id_number' => '26-9876',
        ], ...$attributes]);
    }
}
