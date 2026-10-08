<?php

namespace Tests\Feature\Administration;

use App\Livewire\Administration\Dashboard;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\LoginLog;
use App\Models\Payout;
use App\Models\PayoutSchedule;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\SmsBlast;
use App\Models\SmsMessage;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_database_has_zero_totals_and_honest_empty_states(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())->test(Dashboard::class)
            ->assertSet('summary.seniors', 0)
            ->assertSet('summary.beneficiaries', 0)
            ->assertSet('summary.sms_rate', null)
            ->assertSee('No scheduled payouts')
            ->assertSee('No audit activity recorded yet.')
            ->assertDontSee('18,427')
            ->call('navigate', 'configuration')->assertSee('No configuration recorded')
            ->call('navigate', 'sms')->assertSee('No broadcasts recorded');
    }

    public function test_overview_uses_registry_enrollment_and_monthly_sms_totals(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 8)->startOfDay());
        $senior = $this->senior();
        $this->senior(['registration_number' => 'TEST-PENDING', 'status' => 'PENDING']);
        $program = Program::create(['name' => 'Test Assistance', 'agency' => 'Test Agency', 'budget' => 1000]);
        Beneficiary::create(['program_id' => $program->id, 'senior_citizen_id' => $senior->id, 'enrolled_on' => today(), 'status' => 'ACTIVE']);
        foreach (['SENT', 'FAILED', 'QUEUED'] as $status) {
            $this->message($status);
        }
        $this->message('SENT')->forceFill(['created_at' => now()->subMonth()])->save();

        Livewire::actingAs(User::factory()->admin()->create())->test(Dashboard::class)
            ->assertSet('summary.seniors', 2)
            ->assertSet('summary.verified', 1)
            ->assertSet('summary.pending', 1)
            ->assertSet('summary.beneficiaries', 1)
            ->assertSet('summary.sms_rate', 50.0)
            ->assertSet('summary.sms_queued', 1)
            ->assertSet('summary.sms_failed', 1)
            ->assertSee('Test Barangay')
            ->assertSee('50.0%');
    }

    public function test_chart_groups_schedule_amounts_and_excludes_voided_and_other_years(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 8)->startOfDay());
        $senior = $this->senior();
        $program = Program::create(['name' => 'Test Assistance', 'agency' => 'Test Agency', 'budget' => 4000]);
        foreach ([['2026-01-10', 'RELEASED', 1000], ['2026-02-10', 'PENDING', 2000], ['2026-02-11', 'VOIDED', 5000], ['2025-01-10', 'RELEASED', 9000]] as $index => [$date, $status, $amount]) {
            $schedule = PayoutSchedule::create(['program_id' => $program->id, 'reference_number' => 'TEST-'.$index, 'scheduled_on' => $date, 'amount' => $amount]);
            Payout::create(['payout_schedule_id' => $schedule->id, 'senior_citizen_id' => $senior->id, 'amount' => $amount, 'status' => $status]);
        }

        $component = Livewire::actingAs(User::factory()->admin()->create())->test(Dashboard::class)
            ->assertSet('distribution.planned', 3000.0)
            ->assertSet('distribution.released', 1000.0)
            ->assertSet('distribution.completion', 33.3)
            ->assertSet('distribution.buckets.0.released', 1000.0)
            ->assertSet('distribution.buckets.1.planned', 2000.0)
            ->assertSee('View exact amounts')
            ->set('period', 'Quarterly')
            ->assertSet('distribution.buckets.0.planned', 3000.0)
            ->assertSet('distribution.buckets.1.planned', 0.0)
            ->set('chartType', 'Bar')->assertSeeHtml('payout-bar-pair');

        $this->assertCount(4, $component->get('distribution')['buckets']);
        $component->call('navigate', 'programs')->assertSee('250%')->assertSee('10,000.00');
    }

    public function test_registry_filters_database_records_and_resets_its_page(): void
    {
        for ($index = 0; $index < 16; $index++) {
            $this->senior(['registration_number' => 'TEST-'.$index]);
        }
        $pending = $this->senior(['registration_number' => 'TEST-PENDING', 'status' => 'PENDING', 'first_name' => '<script>example</script>']);

        $component = Livewire::actingAs(User::factory()->admin()->create())->test(Dashboard::class)
            ->call('navigate', 'records')
            ->call('gotoPage', 2, 'recordsPage')
            ->set('recordFilter', 'PENDING')
            ->assertSee($pending->registration_number)
            ->assertDontSee('<script>example</script>', false);

        $this->assertSame(1, $component->get('filteredRecords')->total());
        $this->assertSame(1, $component->get('filteredRecords')->currentPage());
        $component->set('recordFilter', 'All')->set('search', 'test barangay');
        $this->assertSame(17, $component->get('filteredRecords')->total());
        $component->set('search', 'missing record')->assertSee('No matching senior records');
    }

    public function test_logs_merge_database_events_without_exposing_payloads(): void
    {
        $admin = User::factory()->admin()->create();
        AuditLog::create(['action' => 'program.created', 'new_values' => ['private' => 'private-test-payload']]);
        LoginLog::create(['event' => 'LOGIN_FAILED', 'success' => false, 'username_attempted' => 'private-test-username']);
        $this->message('FAILED');

        $component = Livewire::actingAs($admin)->test(Dashboard::class)
            ->call('navigate', 'system-logs')
            ->assertSee('program.created')->assertSee('LOGIN_FAILED')
            ->assertDontSee('private-test-payload')->assertDontSee('private-test-username');

        $this->assertSame(3, $component->get('filteredLogs')->total());
        $component->set('logCategory', 'Login Events');
        $this->assertSame(1, $component->get('filteredLogs')->total());
        $component->set('search', 'missing')->assertSee('No matching system events');
    }

    public function test_registry_age_bands_use_exact_birthday_boundaries(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 8)->startOfDay());
        foreach (['1966-10-09', '1966-10-08', '1956-10-09', '1956-10-08', '1946-10-09', '1946-10-08'] as $index => $birthDate) {
            $this->senior(['registration_number' => 'AGE-'.$index, 'birth_date' => $birthDate]);
        }

        Livewire::actingAs(User::factory()->admin()->create())->test(Dashboard::class)
            ->call('navigate', 'records')
            ->assertSet('registryCounts', ['Total registry' => 6, 'Age 60-69' => 2, 'Age 70-79' => 2, 'Age 80+' => 1]);
    }

    public function test_log_filter_resets_pagination_and_program_search_uses_database_values(): void
    {
        for ($index = 0; $index < 16; $index++) {
            AuditLog::create(['action' => 'program.created']);
        }
        LoginLog::create(['event' => 'LOGIN', 'success' => true]);
        Program::create(['name' => 'Test Program', 'agency' => 'Test Agency', 'budget' => 0]);

        $component = Livewire::actingAs(User::factory()->admin()->create())->test(Dashboard::class)
            ->call('navigate', 'system-logs')->call('gotoPage', 2, 'logsPage')
            ->set('logCategory', 'Login Events')->assertSee('LOGIN');

        $this->assertSame(1, $component->get('filteredLogs')->currentPage());
        $component->call('navigate', 'programs')->set('search', 'TEST AGENCY')->assertSee('Test Program')->assertSee('No budget set');
        $component->set('search', 'missing')->assertSee('No matching programs');
    }

    public function test_broadcasts_and_configuration_show_only_recorded_safe_data(): void
    {
        $admin = User::factory()->admin()->create();
        $blast = SmsBlast::create(['created_by' => $admin->id, 'recipient_count' => 2, 'message' => 'Test broadcast']);
        $this->message('SENT', ['sms_blast_id' => $blast->id]);
        $this->message('FAILED', ['sms_blast_id' => $blast->id]);
        SystemConfiguration::query()->insert([
            ['key' => 'office_name', 'value' => 'Test Office'],
            ['key' => 'private_setting', 'value' => 'private-test-setting'],
        ]);

        $component = Livewire::actingAs($admin)->test(Dashboard::class)
            ->assertSee('Test Office')->assertDontSee('private-test-setting')
            ->call('navigate', 'sms')->assertSeeHtml('href="'.route('sms.blasts.show', $blast).'"');

        $this->assertSame(1, $component->get('broadcasts')->first()->sent_count);
        $this->assertSame(1, $component->get('broadcasts')->first()->failed_count);
        $component->call('navigate', 'configuration')->assertSee('Test Office')->assertDontSee('private-test-setting');
    }

    private function senior(array $attributes = []): SeniorCitizen
    {
        $barangay = Barangay::firstOrCreate(['code' => 'TEST'], ['name' => 'Test Barangay']);

        return SeniorCitizen::create(array_replace([
            'barangay_id' => $barangay->id, 'registration_number' => 'TEST-REGISTRY',
            'first_name' => 'Test', 'last_name' => 'Senior', 'birth_date' => '1950-01-01',
            'sex' => 'FEMALE', 'address' => 'Test address', 'status' => 'VERIFIED',
        ], $attributes));
    }

    private function message(string $status, array $attributes = []): SmsMessage
    {
        return SmsMessage::create(array_replace(['recipient_number' => 'test-recipient', 'message' => 'Test message', 'status' => $status], $attributes));
    }
}
