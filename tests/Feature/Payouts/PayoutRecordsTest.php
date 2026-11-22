<?php

namespace Tests\Feature\Payouts;

use App\Livewire\Payouts\Details;
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

    public function test_legacy_payout_details_are_viewable_without_leaving_the_payouts_page(): void
    {
        $this->actingAs(User::factory()->create());
        $legacy = $this->legacy();

        Livewire::test(Details::class)->call('open', 'legacy', $legacy->id)
            ->assertSee('Payout Details')->assertSee($legacy->seniorCitizen->surname_first_name)
            ->assertSee('Localized Social Pension')->assertViewHas('canClaim', false)
            ->call('close')->assertDontSee('Payout Details');

        $this->assertDatabaseCount('benefit_payments', 0);
    }

    public function test_lists_legacy_payouts_and_pending_entitlements_with_distinct_links_and_unknown_amounts(): void
    {
        $this->actingAs(User::factory()->create());
        $legacy = $this->legacy();
        $entry = $this->entitlement();

        Livewire::test(Index::class)
            ->assertViewHas('payouts', fn ($rows) => $rows->total() === 2)
            ->assertSee('Localized Social Pension')->assertSee('National Social Pension')
            ->assertSee('Not yet assigned')->assertSee('2026-Q4')
            ->assertSeeHtml("source: 'legacy', payoutId: {$legacy->id}")
            ->assertSeeHtml("source: 'benefit', payoutId: {$entry->id}")
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

    public function test_status_filters_include_legacy_claims_and_treat_proxy_claims_as_a_subset(): void
    {
        $this->actingAs(User::factory()->create());
        $legacy = $this->legacy();
        $legacy->update(['status' => 'RELEASED', 'claimant_type' => 'PROXY']);
        $entry = $this->entitlement(['status' => 'CLAIMED', 'amount' => 500]);
        $schedule = BenefitSchedule::create(['program_id' => $entry->program_id, 'reference_number' => 'SYN-FILTER', 'period_key' => '2026-Q4', 'eligibility_on' => today(), 'scheduled_at' => now(), 'location' => 'Synthetic venue', 'barangay_ids' => [], 'rule_snapshot' => [], 'created_by' => auth()->id()]);
        $proxy = BenefitPayment::create(['benefit_entitlement_id' => $entry->id, 'benefit_schedule_id' => $schedule->id, 'reference_number' => 'SYN-PROXY', 'amount' => 500, 'claimant_type' => 'REPRESENTATIVE', 'recorded_by' => auth()->id(), 'paid_at' => now()]);
        $self = $entry->replicate();
        $self->period_key = '2026-Q3';
        $self->save();
        BenefitPayment::create(['benefit_entitlement_id' => $self->id, 'benefit_schedule_id' => $schedule->id, 'reference_number' => 'SYN-SELF', 'amount' => 500, 'claimant_type' => 'SELF', 'recorded_by' => auth()->id(), 'paid_at' => now()]);
        foreach (['2026-Q2' => 'PENDING', '2026-Q1' => 'UNPAID', '2025-Q4' => 'UNCLAIMED'] as $period => $status) {
            $copy = $entry->replicate();
            $copy->fill(['period_key' => $period, 'status' => $status]);
            $copy->save();
        }

        $component = Livewire::test(Index::class)->assertSet('status', '')->assertSet('sort', 'name_asc');

        $document = new \DOMDocument;
        @$document->loadHTML($component->html());
        $statusOptions = [];
        foreach ((new \DOMXPath($document))->query('//select[@aria-label="Status"]/option') as $option) {
            $statusOptions[] = trim($option->textContent);
        }
        $this->assertSame(['All statuses', 'Pending', 'Unclaimed', 'Claimed'], $statusOptions);

        $this->assertSame(6, $component->viewData('counts')->sum());
        $component->assertViewHas('proxyCount', 2);
        foreach (['PENDING' => 2, 'UNCLAIMED' => 1, 'CLAIMED' => 3] as $status => $expected) {
            $component->set('status', $status)->assertHasNoErrors();
            $this->assertSame($expected, $component->viewData('payouts')->total());
            $this->assertSame($expected, $component->viewData('counts')->sum());
        }
        $proxy->update(['reversed_at' => now()]);
        $entry->update(['status' => 'UNPAID']);
        $component->call('$refresh')->assertViewHas('proxyCount', 1);
        $this->assertSame(2, $component->viewData('payouts')->total());
        $this->assertSame(1, $component->viewData('payouts')->where('source', 'legacy')->count());
        $component->set('sort', 'claimed_first')->call('resetFilters')->assertSet('status', '')->assertSet('sort', 'name_asc');
        $this->assertSame(6, $component->viewData('payouts')->total());
        $component->set('status', 'CLAIMED_PROXY')->assertHasErrors('status');
        $this->get(route('payouts.index', ['status' => 'CLAIMED', 'sort' => 'claimed_first']))->assertOk();
    }

    public function test_four_sort_modes_work_with_status_filters_and_reset_pagination(): void
    {
        $this->actingAs(User::factory()->create());
        $legacy = $this->legacy();
        $legacy->seniorCitizen->update(['last_name' => 'Zulu']);
        $entry = $this->entitlement(['status' => 'CLAIMED']);
        $entry->seniorCitizen->update(['last_name' => 'Alpha']);
        $unclaimed = $entry->replicate();
        $unclaimed->fill(['period_key' => '2026-Q3', 'status' => 'UNCLAIMED']);
        $unclaimed->save();
        $alphaClaimed = 'benefit:'.$entry->id;
        $alphaUnclaimed = 'benefit:'.$unclaimed->id;
        $zuluPending = 'legacy:'.$legacy->id;
        $component = Livewire::test(Index::class);

        foreach ([
            'name_asc' => [$alphaUnclaimed, $alphaClaimed, $zuluPending],
            'name_desc' => [$zuluPending, $alphaUnclaimed, $alphaClaimed],
            'pending_first' => [$alphaUnclaimed, $zuluPending, $alphaClaimed],
            'claimed_first' => [$alphaClaimed, $alphaUnclaimed, $zuluPending],
        ] as $sort => $expected) {
            $component->call('gotoPage', 2)->set('sort', $sort)->assertSet('paginators.page', 1)->assertHasNoErrors();
            $this->assertSame($expected, $component->viewData('payouts')->map(fn ($row) => $row->source.':'.$row->id)->all());
        }
        $component->set('status', 'UNCLAIMED')->set('sort', 'name_desc');
        $this->assertSame([$unclaimed->id], $component->viewData('payouts')->pluck('id')->all());
        $component->set('sort', 'unsafe_column')->assertHasErrors(['sort']);
        $component->call('resetFilters')->assertSet('sort', 'name_asc')->assertSet('status', '')->assertHasNoErrors();
    }

    public function test_default_order_uses_actual_surname_first_and_middle_names_across_payout_sources(): void
    {
        $this->actingAs(User::factory()->create());
        $legacy = $this->legacy();
        $legacy->seniorCitizen->update(['first_name' => 'Aaron', 'middle_name' => 'Alpha', 'last_name' => 'Zulu', 'osca_id_number' => '26-0001']);
        $entry = $this->entitlement(['status' => 'CLAIMED']);
        $entry->seniorCitizen->update(['first_name' => 'Zara', 'last_name' => 'Alpha', 'osca_id_number' => '26-9999']);
        $middleTie = $legacy->seniorCitizen->replicate();
        $middleTie->fill(['middle_name' => 'Beta', 'osca_id_number' => '26-0002']);
        $middleTie->save();
        $middlePayout = $legacy->replicate();
        $middlePayout->senior_citizen_id = $middleTie->id;
        $middlePayout->save();
        $firstTie = $legacy->seniorCitizen->replicate();
        $firstTie->fill(['first_name' => 'Zara', 'middle_name' => 'Alpha', 'osca_id_number' => '26-0003']);
        $firstTie->save();
        $firstPayout = $legacy->replicate();
        $firstPayout->senior_citizen_id = $firstTie->id;
        $firstPayout->save();
        $repeat = $entry->replicate();
        $repeat->period_key = '2026-Q3';
        $repeat->save();

        $component = Livewire::test(Index::class);

        $this->assertSame([
            'benefit:'.$repeat->id, 'benefit:'.$entry->id, 'legacy:'.$legacy->id,
            'legacy:'.$middlePayout->id, 'legacy:'.$firstPayout->id,
        ], $component->viewData('payouts')->map(fn ($row) => $row->source.':'.$row->id)->all());
        $component->assertDontSee('Sort payouts');
    }

    public function test_alphabetical_order_is_applied_before_pagination_and_survives_search_and_filters(): void
    {
        $this->actingAs(User::factory()->create());
        $legacy = $this->legacy();
        $expected = [];
        foreach (range(18, 1) as $number) {
            $senior = $legacy->seniorCitizen->replicate();
            $senior->fill(['last_name' => 'Synthetic '.str_pad((string) $number, 2, '0', STR_PAD_LEFT), 'osca_id_number' => '26-'.str_pad((string) (100 - $number), 4, '0', STR_PAD_LEFT)]);
            $senior->save();
            $payout = $legacy->replicate();
            $payout->senior_citizen_id = $senior->id;
            $payout->save();
            array_unshift($expected, $payout->id);
        }

        $component = Livewire::test(Index::class)->set('search', 'Synthetic')
            ->set('status', 'PENDING')->set('programId', (string) $legacy->schedule->program_id)
            ->set('barangayId', (string) $legacy->seniorCitizen->barangay_id);

        $this->assertSame(array_slice($expected, 0, 15), $component->viewData('payouts')->pluck('id')->all());
        $component->call('nextPage')->assertSet('paginators.page', 2);
        $this->assertSame(array_slice($expected, 15), $component->viewData('payouts')->pluck('id')->all());
        $component->call('previousPage');
        $this->assertSame(array_slice($expected, 0, 15), $component->viewData('payouts')->pluck('id')->all());
        $component->set('search', 'Synthetic 18')->assertSet('paginators.page', 1);
        $this->assertSame([$expected[17]], $component->viewData('payouts')->pluck('id')->all());
    }

    public function test_table_has_seven_columns_and_surname_first_names_for_both_payout_sources(): void
    {
        $this->actingAs(User::factory()->create());
        $legacy = $this->legacy();
        $legacy->seniorCitizen->update(['first_name' => 'Primo', 'middle_name' => 'Lee', 'last_name' => 'Suav']);
        $entry = $this->entitlement();
        $entry->seniorCitizen->update(['first_name' => 'Ana', 'middle_name' => null, 'last_name' => 'Synthetic']);

        $component = Livewire::test(Index::class)->assertSee('SUAV, PRIMO LEE')->assertSee('SYNTHETIC, ANA')
            ->assertSee('Not yet assigned')->assertDontSee('Awaiting amount');

        $document = new \DOMDocument;
        @$document->loadHTML($component->html());
        $xpath = new \DOMXPath($document);
        $headings = [];
        foreach ($xpath->query('//table/thead/tr/th') as $heading) {
            $headings[] = trim($heading->textContent);
        }
        $this->assertSame(['Beneficiary', 'OSCA ID', 'Barangay', 'Program', 'Amount', 'Claim Status', 'Action'], $headings);
        Livewire::test(Details::class)->call('open', 'benefit', $entry->id)->assertSee('SYNTHETIC, ANA');
    }

    public function test_compact_pagination_preserves_filters_and_filter_changes_return_to_the_first_page(): void
    {
        $this->freezeTime();
        $this->actingAs(User::factory()->create());
        $first = $this->legacy();
        $this->entitlement(['status' => 'UNCLAIMED']);

        foreach (range(1, 15) as $number) {
            $senior = SeniorCitizen::create([
                'barangay_id' => $first->seniorCitizen->barangay_id, 'first_name' => 'Synthetic '.$number,
                'last_name' => 'Pagination', 'birth_date' => '1948-01-01', 'sex' => 'FEMALE',
                'address' => 'Synthetic address', 'status' => 'VERIFIED',
            ]);
            Payout::create([
                'payout_schedule_id' => $first->payout_schedule_id, 'senior_citizen_id' => $senior->id,
                'amount' => 500, 'status' => 'PENDING',
            ]);
        }

        Livewire::test(Index::class)->set('status', 'PENDING')
            ->assertSeeHtml('aria-label="Payout records pagination"')
            ->assertSeeHtml('aria-current="page" aria-label="Page 1"')
            ->assertViewHas('payouts', fn ($rows) => $rows->count() === 15 && $rows->total() === 16)
            ->call('nextPage')->assertSet('paginators.page', 2)->assertSet('status', 'PENDING')
            ->assertViewHas('payouts', fn ($rows) => $rows->count() === 1 && $rows->first()->id === $first->id)
            ->assertSeeHtml('aria-current="page" aria-label="Page 2"')
            ->call('previousPage')->assertSet('paginators.page', 1)
            ->call('gotoPage', 2)->assertSet('status', 'PENDING')
            ->set('search', 'No matching synthetic beneficiary')->assertSet('paginators.page', 1)
            ->assertSee('No matching payouts.')
            ->assertDontSeeHtml('aria-label="Payout records pagination"')
            ->call('resetFilters')->assertSet('status', '')->assertSet('search', '')
            ->assertViewHas('payouts', fn ($rows) => $rows->total() === 17);
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
