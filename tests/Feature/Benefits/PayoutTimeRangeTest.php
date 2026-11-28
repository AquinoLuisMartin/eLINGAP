<?php

namespace Tests\Feature\Benefits;

use App\Livewire\Benefits\Workspace;
use App\Models\BenefitSchedule;
use App\Models\Program;
use App\Models\User;
use App\Services\Benefits\BenefitSms;
use Database\Seeders\BenefitProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PayoutTimeRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_and_sms_show_the_saved_start_and_end_times(): void
    {
        $this->travelTo(now()->setDate(2026, 12, 10)->setTime(8, 0));
        $this->actingAs(User::factory()->create());
        $this->seed(BenefitProgramSeeder::class);
        $program = Program::where('benefit_code', 'december-assistance')->sole();

        Livewire::test(Workspace::class, ['initialProgramId' => (string) $program->id])
            ->set('form.location', 'Synthetic venue')->set('historicalClaimsChecked', true)
            ->set('form.scheduled_at', '2026-12-10T09:00')->set('form.ends_at', '2026-12-10T15:30')
            ->call('createSchedule')->assertHasNoErrors();

        $schedule = BenefitSchedule::sole();
        $this->assertSame('2026-12-10 09:00', $schedule->scheduled_at->format('Y-m-d H:i'));
        $this->assertSame('2026-12-10 15:30', $schedule->ends_at->format('Y-m-d H:i'));
        $this->assertStringContainsString('Payout: Dec 10, 2026 9:00 AM - 3:30 PM (Philippine time)', app(BenefitSms::class)->template($schedule));
        $this->get(route('payout-schedules.index'))->assertOk()->assertSee('9:00 AM - 3:30 PM');
        $this->get(route('sms.blasts.create'))->assertOk()->assertSee('Scheduled payout')->assertSee('Synthetic venue')->assertSee('9:00 AM - 3:30 PM');
        $this->get(route('sms.blasts.create', ['benefit_schedule_id' => $schedule->id]))->assertOk()
            ->assertSee('Payout SMS announcement')->assertSee('Location: Synthetic venue')
            ->assertSee('Payout: Dec 10, 2026 9:00 AM - 3:30 PM (Philippine time)')
            ->assertSee('Recipient approval required before sending.')->assertDontSee('Compose message');
        $this->assertDatabaseCount('sms_messages', 0);
    }

    #[TestWith(['', 'The payout end time field is required.'])]
    #[TestWith(['invalid', 'Choose a valid payout end time.'])]
    #[TestWith(['2026-12-10T15:00+08:00', 'Choose a valid payout end time.'])]
    #[TestWith(['2026-12-10T09:00', 'Choose an end time after the start time on the same payout date.'])]
    #[TestWith(['2026-12-10T08:59', 'Choose an end time after the start time on the same payout date.'])]
    #[TestWith(['2026-12-11T00:00', 'Choose an end time after the start time on the same payout date.'])]
    public function test_end_time_must_be_later_on_the_same_payout_date(string $end, string $message): void
    {
        $this->travelTo(now()->setDate(2026, 12, 10)->setTime(8, 0));
        $this->actingAs(User::factory()->create());
        $this->seed(BenefitProgramSeeder::class);
        $program = Program::where('benefit_code', 'december-assistance')->sole();

        Livewire::test(Workspace::class, ['initialProgramId' => (string) $program->id])
            ->set('form.location', 'Synthetic venue')->set('historicalClaimsChecked', true)
            ->set('form.scheduled_at', '2026-12-10T09:00')->set('form.ends_at', $end)
            ->call('createSchedule')->assertHasErrors('form.ends_at')
            ->assertSeeHtml('id="payout-end-time-error"')->assertSeeHtml('>'.e($message).'</p>');

        $this->assertDatabaseCount('benefit_schedules', 0);
        $this->assertSame('UPCOMING', $program->fresh()->status);
    }

    public function test_schedule_accepts_midnight_as_the_start_and_noon_as_the_end(): void
    {
        $this->travelTo(now()->setDate(2026, 12, 10)->setTime(8, 0));
        $this->actingAs(User::factory()->create());
        $this->seed(BenefitProgramSeeder::class);
        $program = Program::where('benefit_code', 'december-assistance')->sole();

        Livewire::test(Workspace::class, ['initialProgramId' => (string) $program->id])
            ->set('form.location', 'Synthetic venue')->set('historicalClaimsChecked', true)
            ->set('form.scheduled_at', '2026-12-10T00:00')->set('form.ends_at', '2026-12-10T12:00')
            ->call('createSchedule')->assertHasNoErrors();

        $this->assertSame('Dec 10, 2026 12:00 AM - 12:00 PM (Philippine time)', BenefitSchedule::sole()->payoutWindow());
    }

    #[TestWith(['24:00'])]
    #[TestWith(['09:60'])]
    #[TestWith(['a9:00'])]
    #[TestWith(['09:a0'])]
    #[TestWith(['009:00'])]
    #[TestWith(['09:000'])]
    public function test_start_and_end_reject_invalid_submitted_clock_values(string $time): void
    {
        $this->travelTo(now()->setDate(2026, 12, 10)->setTime(8, 0));
        $this->actingAs(User::factory()->create());
        $this->seed(BenefitProgramSeeder::class);
        $program = Program::where('benefit_code', 'december-assistance')->sole();

        Livewire::test(Workspace::class, ['initialProgramId' => (string) $program->id])
            ->set('form.location', 'Synthetic venue')->set('historicalClaimsChecked', true)
            ->set('form.scheduled_at', '2026-12-10T'.$time)->set('form.ends_at', '2026-12-10T'.$time)
            ->call('createSchedule')->assertHasErrors(['form.scheduled_at' => 'date_format', 'form.ends_at' => 'date_format']);

        $this->assertDatabaseEmpty('benefit_schedules');
    }

    public function test_existing_schedules_without_end_times_keep_their_original_time(): void
    {
        $schedule = new BenefitSchedule(['scheduled_at' => '2026-12-10 09:00', 'ends_at' => null]);

        $this->assertSame('Dec 10, 2026 9:00 AM (Philippine time)', $schedule->payoutWindow());
    }

    public function test_schedule_list_displays_existing_schedules_without_end_times(): void
    {
        $this->actingAs(User::factory()->create());
        $this->seed(BenefitProgramSeeder::class);
        $program = Program::where('benefit_code', 'december-assistance')->sole();
        BenefitSchedule::create(['program_id' => $program->id, 'reference_number' => 'SYNTHETIC-TIME',
            'period_key' => '2026', 'eligibility_on' => '2026-12-10', 'scheduled_at' => '2026-12-10 09:00',
            'ends_at' => null, 'location' => 'Synthetic venue', 'barangay_ids' => [],
            'rule_snapshot' => $program->benefit_rules, 'created_by' => auth()->id()]);

        $this->get(route('payout-schedules.index'))->assertOk()
            ->assertSee('Dec 10, 2026 9:00 AM (Philippine time)')->assertSee('Synthetic venue');
    }
}
