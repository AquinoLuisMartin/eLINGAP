<?php

namespace Tests\Feature\Payouts;

use App\Livewire\Benefits\SchedulePanel;
use App\Livewire\Benefits\Workspace;
use App\Models\BenefitSchedule;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\BenefitProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PayoutScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_payout_program_dropdown_includes_catalog_enrolled_programs_and_other(): void
    {
        $this->actingAs(User::factory()->create());
        $this->seed(BenefitProgramSeeder::class);
        Program::create(['name' => 'Synthetic enrolled program', 'agency' => 'OSCA', 'status' => 'ACTIVE']);
        Program::create(['name' => 'Synthetic inactive program', 'agency' => 'OSCA', 'status' => 'INACTIVE']);

        $this->get(route('payout-schedules.create'))
            ->assertSee('Annual December Senior Assistance')
            ->assertSee('National Social Pension')
            ->assertSee('Synthetic enrolled program')
            ->assertSee('Other - enter a new program')
            ->assertDontSee('Synthetic inactive program');
    }

    public function test_catalog_selection_opens_the_reviewed_schedule_form_inside_payouts(): void
    {
        $this->actingAs(User::factory()->create());
        $this->seed(BenefitProgramSeeder::class);
        $program = Program::where('benefit_code', 'december-assistance')->sole();

        $this->get(route('payout-schedules.create', ['program_id' => $program->id]))
            ->assertSeeLivewire(Workspace::class)
            ->assertSee('Prepare eligible recipient list')
            ->assertSee('Activate this program when the schedule is created.')
            ->assertDontSee('Benefits Workspace</h2>', false);

        Livewire::test(Workspace::class, ['initialProgramId' => (string) $program->id, 'payoutMode' => true])
            ->assertSet('form.program_id', (string) $program->id)
            ->assertSet('form.amount', '500.00')
            ->assertSet('payoutMode', true);
        $this->assertSame('UPCOMING', $program->fresh()->status);
        $this->assertDatabaseCount('benefit_schedules', 0);
    }

    public function test_other_can_create_a_new_program_and_schedule_from_the_payout_page(): void
    {
        $this->travelTo(now()->setDate(2026, 12, 10)->setTime(12, 0));
        $staff = User::factory()->create();
        $this->actingAs($staff);

        $this->get(route('payout-schedules.create', ['program_id' => 'other']))
            ->assertSeeLivewire(Workspace::class)
            ->assertSee('Program name')
            ->assertSee('Government level');

        Livewire::test(Workspace::class, ['initialProgramId' => 'other', 'payoutMode' => true])
            ->set('newProgram.name', 'Synthetic payout assistance')
            ->set('form.location', 'Synthetic payout venue')
            ->set('form.amount', '750')
            ->set('historicalClaimsChecked', true)
            ->call('createSchedule')->assertHasNoErrors()
            ->assertSeeLivewire(SchedulePanel::class);

        $program = Program::where('name', 'Synthetic payout assistance')->sole();
        $schedule = BenefitSchedule::sole();
        $this->assertSame($program->id, $schedule->program_id);
        $this->assertSame($staff->id, $schedule->created_by);
        $this->assertSame('DRAFT', $schedule->status);
        $this->assertDatabaseCount('benefit_payments', 0);
        $this->get(route('payout-schedules.index'))->assertSee('Synthetic payout assistance')->assertSee($schedule->reference_number);
        $this->get(route('payout-schedules.create', ['program_id' => $program->id, 'schedule_id' => $schedule->id]))
            ->assertSee('Review payout schedule')->assertSee($schedule->reference_number)->assertSeeLivewire(SchedulePanel::class);
    }

    public function test_enrolled_program_retains_the_existing_payout_form(): void
    {
        $this->actingAs(User::factory()->create());
        $program = Program::create(['name' => 'Synthetic enrolled program', 'agency' => 'OSCA', 'status' => 'ACTIVE']);

        $this->get(route('payout-schedules.create', ['program_id' => $program->id]))
            ->assertSee('Reference number')
            ->assertSee('Scheduled date')
            ->assertSee('Create schedule')
            ->assertDontSee('Prepare eligible recipient list');
    }

    public function test_payout_selection_rejects_inactive_programs_and_mismatched_schedules(): void
    {
        $this->actingAs(User::factory()->create());
        $inactive = Program::create(['name' => 'Synthetic inactive program', 'agency' => 'OSCA', 'status' => 'INACTIVE']);
        $this->seed(BenefitProgramSeeder::class);
        $program = Program::where('benefit_code', 'december-assistance')->sole();
        $other = Program::where('benefit_code', 'lgu-age-90')->sole();
        $schedule = $this->schedule($program);

        $this->get(route('payout-schedules.create', ['program_id' => $inactive->id]))->assertUnprocessable();
        $this->get(route('payout-schedules.create', ['program_id' => $other->id, 'schedule_id' => $schedule->id]))->assertNotFound();
        $this->assertDatabaseCount('benefit_schedules', 1);
    }

    public function test_existing_schedules_remain_reviewable_when_the_program_becomes_inactive(): void
    {
        $this->actingAs(User::factory()->create());
        $this->seed(BenefitProgramSeeder::class);
        $program = Program::where('benefit_code', 'december-assistance')->sole();
        $schedule = $this->schedule($program);
        $program->update(['status' => 'INACTIVE']);

        $this->get(route('payout-schedules.create', ['program_id' => $program->id, 'schedule_id' => $schedule->id]))
            ->assertSee('Review payout schedule')->assertSee($schedule->reference_number);
    }

    public function test_admin_can_review_schedules_but_cannot_open_staff_program_creation(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->seed(BenefitProgramSeeder::class);
        $program = Program::where('benefit_code', 'december-assistance')->sole();
        $schedule = $this->schedule($program);

        $this->get(route('payout-schedules.create', ['program_id' => 'other']))->assertForbidden();
        $this->get(route('payout-schedules.create', ['program_id' => $program->id]))->assertForbidden();
        $this->get(route('payout-schedules.create', ['program_id' => $program->id, 'schedule_id' => $schedule->id]))
            ->assertSee($schedule->reference_number)->assertDontSee('Prepare eligible recipient list');
    }

    public function test_guests_cannot_open_payout_scheduling(): void
    {
        $this->get(route('payout-schedules.create'))->assertRedirect(route('login'));
    }

    private function schedule(Program $program): BenefitSchedule
    {
        return BenefitSchedule::create([
            'program_id' => $program->id, 'reference_number' => 'SCH-SYNTHETIC-REVIEW', 'period_key' => '2026',
            'eligibility_on' => today(), 'scheduled_at' => now(), 'location' => 'Synthetic venue', 'barangay_ids' => [],
            'rule_snapshot' => [...$program->benefit_rules, 'government_level' => $program->government_level],
            'amount' => '500', 'available_funds' => '10000', 'created_by' => auth()->id(),
        ]);
    }
}
