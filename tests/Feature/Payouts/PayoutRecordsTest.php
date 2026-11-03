<?php

namespace Tests\Feature\Payouts;

use App\Livewire\Payouts\Index;
use App\Models\Barangay;
use App\Models\BenefitEntitlement;
use App\Models\BenefitPayment;
use App\Models\BenefitSchedule;
use App\Models\Payout;
use App\Models\PayoutSchedule;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\User;
use Database\Seeders\BenefitProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PayoutRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_legacy_payouts_and_pending_entitlements_with_distinct_links_and_unknown_amounts(): void
    {
        $this->actingAs(User::factory()->create());
        $legacy = $this->legacy();
        $entry = $this->entitlement();

        Livewire::test(Index::class)
            ->assertViewHas('payouts', fn ($rows) => $rows->total() === 2)
            ->assertSee('Localized Social Pension')->assertSee('National Social Pension')
            ->assertSee('Awaiting amount')->assertSee('2026-Q4')
            ->assertSeeHtml('href="'.route('payouts.show', $legacy).'"')
            ->assertSeeHtml('href="'.route('senior-citizens.show', $entry->senior_citizen_id).'"')
            ->assertSeeHtml('wire:key="payout-legacy-'.$legacy->id.'"')
            ->assertSeeHtml('wire:key="payout-benefit-'.$entry->id.'"');
    }

    public function test_benefit_filters_combine_search_status_program_and_barangay_and_reset(): void
    {
        $this->actingAs(User::factory()->create());
        $legacy = $this->legacy();
        $entry = $this->entitlement(['status' => 'UNCLAIMED']);

        Livewire::test(Index::class)->set('search', 'Benefit Recipient')
            ->set('status', 'UNCLAIMED')->set('programId', (string) $entry->program_id)
            ->set('barangayId', (string) $entry->seniorCitizen->barangay_id)
            ->assertHasNoErrors()->assertViewHas('payouts', fn ($rows) => $rows->total() === 1 && $rows->first()->source === 'benefit')
            ->assertDontSee($legacy->seniorCitizen->full_name)
            ->call('resetFilters')->assertViewHas('payouts', fn ($rows) => $rows->total() === 2);

        $this->get(route('payouts.index', ['status' => 'UNCLAIMED']))->assertSeeLivewire(Index::class);
    }

    public function test_claimed_proxy_count_ignores_reversed_payments_and_pending_records(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $entry = $this->entitlement(['status' => 'CLAIMED', 'amount' => 500]);
        $schedule = BenefitSchedule::create([
            'program_id' => $entry->program_id, 'reference_number' => 'SYN-PROXY', 'period_key' => '2026-Q4',
            'eligibility_on' => today(), 'scheduled_at' => now(), 'location' => 'Synthetic venue',
            'barangay_ids' => [], 'requirements' => 'Synthetic requirements',
            'rule_snapshot' => ['cadence' => 'QUARTERLY'], 'created_by' => $user->id,
        ]);
        $payment = BenefitPayment::create([
            'benefit_entitlement_id' => $entry->id, 'benefit_schedule_id' => $schedule->id,
            'reference_number' => 'SYN-PAYMENT', 'amount' => 500, 'claimant_type' => 'REPRESENTATIVE',
            'recorded_by' => $user->id, 'paid_at' => now(),
        ]);
        Livewire::test(Index::class)->assertViewHas('proxyCount', 1)
            ->assertViewHas('counts', fn ($counts) => $counts['CLAIMED'] === 1);

        $payment->update(['reversed_at' => now(), 'reversed_by' => $user->id, 'reversal_reason' => 'Synthetic reversal']);
        $entry->update(['status' => 'PENDING']);

        Livewire::test(Index::class)->assertViewHas('proxyCount', 0)
            ->assertViewHas('counts', fn ($counts) => $counts['PENDING'] === 1)
            ->assertDontSee('>Representative</td>', false);
    }

    public function test_guest_cannot_access_combined_payout_records(): void
    {
        $this->get(route('payouts.index'))->assertRedirect(route('login'));
    }

    private function legacy(): Payout
    {
        $this->seed(BenefitProgramSeeder::class);
        $senior = $this->senior('Legacy');
        $program = Program::where('benefit_code', 'local-social-pension')->sole();
        $program->update(['status' => 'ACTIVE']);
        $schedule = PayoutSchedule::create(['program_id' => $program->id, 'reference_number' => 'SYN-LEGACY', 'scheduled_on' => today(), 'amount' => 500]);

        return Payout::create(['payout_schedule_id' => $schedule->id, 'senior_citizen_id' => $senior->id, 'amount' => 500, 'status' => 'PENDING']);
    }

    private function entitlement(array $attributes = []): BenefitEntitlement
    {
        $this->seed(BenefitProgramSeeder::class);
        $senior = $this->senior('Benefit');
        $program = Program::where('benefit_code', 'national-social-pension')->sole();
        $program->update(['status' => 'ACTIVE']);

        return BenefitEntitlement::create([...[
            'program_id' => $program->id, 'senior_citizen_id' => $senior->id, 'period_key' => '2026-Q4',
            'eligibility_on' => today(), 'rule_snapshot' => ['cadence' => 'QUARTERLY'], 'status' => 'PENDING',
        ], ...$attributes]);
    }

    private function senior(string $name): SeniorCitizen
    {
        $barangay = Barangay::create(['name' => 'Synthetic '.$name.' area', 'code' => strtoupper($name)]);

        return SeniorCitizen::create(['barangay_id' => $barangay->id, 'first_name' => $name, 'last_name' => 'Recipient',
            'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Synthetic address', 'status' => 'VERIFIED']);
    }
}
