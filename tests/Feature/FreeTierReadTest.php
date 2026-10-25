<?php

namespace Tests\Feature;

use App\Livewire\Benefits\EntitlementReport;
use App\Livewire\Benefits\SchedulePanel;
use App\Models\Barangay;
use App\Models\BenefitEntitlement;
use App\Models\BenefitPayment;
use App\Models\BenefitSchedule;
use App\Models\Payout;
use App\Models\PayoutSchedule;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\SmsBlast;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class FreeTierReadTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_sms_lists_keep_delivery_information_with_a_small_database_payload(bool $broadcast): void
    {
        $staff = User::factory()->create();
        $senior = $this->senior();
        $blast = $broadcast ? SmsBlast::create(['created_by' => $staff->id, 'message' => 'Synthetic broadcast']) : null;
        SmsMessage::create([
            'senior_citizen_id' => $senior->id, 'sms_blast_id' => $blast?->id,
            'recipient_number' => '+639170000001', 'message' => $broadcast ? str_repeat('Synthetic body ', 100) : 'Synthetic message',
            'status' => 'FAILED', 'failure_reason' => 'Synthetic delivery failure', 'queued_at' => now(),
        ]);

        $response = $this->actingAs($staff)->get($broadcast ? route('sms.blasts.show', $blast) : route('sms.messages.index'));

        $response->assertSee('Synthetic Middle Applicant Jr.')->assertSee('Synthetic delivery failure')
            ->assertSee('Retry')->assertSee($broadcast ? 'Synthetic broadcast' : 'Synthetic message');
        $this->assertLessThan(4096, strlen(json_encode($response->viewData('messages')->items(), JSON_THROW_ON_ERROR)));
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_payout_lists_keep_names_and_links_with_a_small_database_payload(bool $schedulePage): void
    {
        $staff = User::factory()->create();
        $senior = $this->senior();
        $program = Program::create(['name' => 'Synthetic program', 'agency' => 'Synthetic office', 'status' => 'ACTIVE', 'benefit_rules' => ['notes' => str_repeat('x', 32000)]]);
        $schedule = PayoutSchedule::create(['program_id' => $program->id, 'reference_number' => 'SYN-PAYOUT', 'scheduled_on' => '2026-10-25', 'amount' => 1000, 'status' => 'SCHEDULED']);
        $payout = Payout::create(['payout_schedule_id' => $schedule->id, 'senior_citizen_id' => $senior->id, 'amount' => 1000, 'status' => 'PENDING']);

        $response = $this->actingAs($staff)->get($schedulePage ? route('payout-schedules.show', $schedule) : route('payouts.index'));

        $response->assertSee('Synthetic Middle Applicant Jr.')->assertSee('Synthetic program')->assertSee(route('payouts.show', $payout));
        $this->assertLessThan(4096, strlen(json_encode($response->viewData('payouts')->items(), JSON_THROW_ON_ERROR)));
    }

    public function test_schedule_directory_does_not_transfer_rule_snapshots_for_each_row(): void
    {
        $staff = User::factory()->create();
        $schedule = $this->benefitSchedule($staff);

        $response = $this->actingAs($staff)->get(route('payout-schedules.index'));

        $response->assertSee('SYN-BENEFIT')->assertSee('Synthetic program')->assertSee('Synthetic venue')
            ->assertSee(route('payout-schedules.create', ['program_id' => $schedule->program_id, 'schedule_id' => $schedule->id]));
        $this->assertLessThan(4096, strlen(json_encode($response->viewData('benefitSchedules')->items(), JSON_THROW_ON_ERROR)));
    }

    #[TestWith([SchedulePanel::class])]
    #[TestWith([EntitlementReport::class])]
    public function test_benefit_lists_keep_payment_history_with_a_small_database_payload(string $componentClass): void
    {
        $this->freezeTime();
        $staff = User::factory()->create();
        $schedule = $this->benefitSchedule($staff);
        $entry = BenefitEntitlement::create([
            'program_id' => $schedule->program_id, 'senior_citizen_id' => $this->senior()->id,
            'benefit_schedule_id' => $schedule->id, 'period_key' => '2026', 'eligibility_on' => '2026-01-01',
            'amount' => 1000, 'status' => 'CLAIMED', 'rule_snapshot' => ['notes' => str_repeat('x', 32000)],
            'review_note' => str_repeat('Synthetic review ', 2000), 'reviewed_at' => now(),
        ]);
        foreach (['SYN-PAY-REVERSED', 'SYN-PAY-CURRENT'] as $reference) {
            BenefitPayment::create([
                'benefit_entitlement_id' => $entry->id, 'benefit_schedule_id' => $schedule->id,
                'reference_number' => $reference, 'amount' => 1000, 'claimant_type' => 'SELF', 'recorded_by' => $staff->id,
                'paid_at' => now(), 'reversed_at' => $reference === 'SYN-PAY-REVERSED' ? now() : null,
                'reversal_reason' => $reference === 'SYN-PAY-REVERSED' ? 'Synthetic correction reason' : null,
            ]);
        }
        $this->actingAs($staff);

        $component = Livewire::test($componentClass, $componentClass === SchedulePanel::class ? ['scheduleId' => $schedule->id] : []);

        $component->assertSee('Synthetic Middle Applicant Jr.')->assertSee('Synthetic program')
            ->assertSee('Synthetic barangay')->assertSee('SYN-PAY-REVERSED')->assertSee('SYN-PAY-CURRENT');
        $this->assertLessThan(8192, strlen(json_encode($component->viewData('entries')->items(), JSON_THROW_ON_ERROR)));
        if ($componentClass === SchedulePanel::class) {
            $this->assertSame(1, $component->viewData('recipientTotal'));
            $this->assertSame('1000.00', $component->viewData('requiredFunds'));
        } else {
            $component->assertSee('Synthetic correction reason')->assertSee(now()->format('M j, Y'));
        }
    }

    public function test_schedule_totals_cover_all_recipients_on_later_pages(): void
    {
        $staff = User::factory()->create();
        $schedule = $this->benefitSchedule($staff);
        $senior = $this->senior();
        foreach (range(1, 16) as $period) {
            BenefitEntitlement::create([
                'program_id' => $schedule->program_id, 'senior_citizen_id' => $senior->id,
                'benefit_schedule_id' => $schedule->id, 'period_key' => 'SYN-'.$period,
                'eligibility_on' => '2026-01-01', 'amount' => 1000, 'status' => 'UNPAID', 'rule_snapshot' => [],
            ]);
        }
        $this->actingAs($staff);

        $component = Livewire::test(SchedulePanel::class, ['scheduleId' => $schedule->id])->call('setPage', 2, 'recipientsPage');

        $this->assertCount(1, $component->viewData('entries'));
        $this->assertSame(16, $component->viewData('recipientTotal'));
        $this->assertSame('16000.00', $component->viewData('requiredFunds'));
    }

    private function senior(): SeniorCitizen
    {
        $barangay = Barangay::create(['name' => 'Synthetic barangay', 'code' => 'SYN']);

        return SeniorCitizen::create([
            'barangay_id' => $barangay->id, 'registration_number' => 'SYN-REG', 'osca_id_number' => 'SYN-ID',
            'first_name' => 'Synthetic', 'middle_name' => 'Middle', 'last_name' => 'Applicant', 'name_suffix' => 'Jr.',
            'birth_date' => '1950-01-01', 'sex' => 'FEMALE', 'address' => 'Synthetic address', 'status' => 'VERIFIED',
            'requirements_checks' => ['notes' => str_repeat('x', 32000)],
        ]);
    }

    private function benefitSchedule(User $staff): BenefitSchedule
    {
        $program = Program::create([
            'name' => 'Synthetic program', 'agency' => 'Synthetic office', 'status' => 'ACTIVE',
            'government_level' => 'LGU', 'benefit_rules' => ['notes' => str_repeat('x', 32000)],
        ]);

        return BenefitSchedule::create([
            'program_id' => $program->id, 'reference_number' => 'SYN-BENEFIT', 'period_key' => '2026',
            'eligibility_on' => '2026-01-01', 'scheduled_at' => '2026-10-25 08:00:00', 'location' => 'Synthetic venue',
            'barangay_ids' => [], 'amount' => 1000, 'available_funds' => 10000, 'status' => 'DRAFT',
            'rule_snapshot' => ['government_level' => 'LGU', 'notes' => str_repeat('x', 32000)], 'created_by' => $staff->id,
        ]);
    }
}
