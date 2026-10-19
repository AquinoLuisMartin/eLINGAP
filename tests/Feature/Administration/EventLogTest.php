<?php

namespace Tests\Feature\Administration;

use App\Livewire\Administration\Dashboard;
use App\Models\AuditLog;
use App\Models\LoginLog;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class EventLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_login_and_sms_events_identify_their_own_operator(): void
    {
        $admin = User::factory()->admin()->create(['first_name' => 'Synthetic', 'last_name' => 'Administrator']);
        $staff = User::factory()->create(['first_name' => 'Synthetic', 'middle_name' => 'Middle', 'last_name' => 'Operator', 'name_suffix' => 'Jr.']);
        AuditLog::create(['user_id' => $staff->id, 'action' => 'access.reports.index']);
        LoginLog::create(['user_id' => $admin->id, 'event' => 'LOGIN', 'success' => true]);
        SmsMessage::create(['created_by' => $staff->id, 'recipient_number' => 'test-recipient', 'message' => 'Synthetic message', 'status' => 'SENT']);

        Livewire::actingAs($admin)->test(Dashboard::class)
            ->assertSee('Accessed: Reports index - OSCA Staff Synthetic Middle Operator Jr.')
            ->call('navigate', 'system-logs')
            ->assertSee('Accessed: Reports index - OSCA Staff Synthetic Middle Operator Jr.')
            ->assertSee('Signed in - Admin Synthetic Administrator')
            ->assertSee('SMS delivery: sent - OSCA Staff Synthetic Middle Operator Jr.');
    }

    public function test_events_without_an_account_remain_visible_with_an_honest_fallback(): void
    {
        $admin = User::factory()->admin()->create();
        AuditLog::create(['action' => 'program.created']);

        Livewire::actingAs($admin)->test(Dashboard::class)
            ->assertSee('Created a program - System / unavailable operator')
            ->call('navigate', 'system-logs')
            ->assertSee('Created a program - System / unavailable operator');
    }

    public function test_operator_search_respects_the_selected_event_category(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create(['first_name' => 'Synthetic', 'last_name' => 'Operator']);
        AuditLog::create(['user_id' => $staff->id, 'action' => 'report.exported']);
        LoginLog::create(['user_id' => $staff->id, 'event' => 'LOGIN', 'success' => true]);

        $component = Livewire::actingAs($admin)->test(Dashboard::class)
            ->call('navigate', 'system-logs')
            ->set('logCategory', 'Audit Trail')
            ->set('search', 'osca staff synthetic operator')
            ->assertSee('Exported a report - OSCA Staff Synthetic Operator')
            ->assertDontSee('Signed in - OSCA Staff Synthetic Operator');

        $this->assertSame(1, $component->get('filteredLogs')->total());
    }

    public function test_legacy_password_resets_do_not_misidentify_the_target_as_the_operator(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create(['first_name' => 'Synthetic', 'last_name' => 'Target']);
        LoginLog::create(['user_id' => $staff->id, 'event' => 'PASSWORD_RESET', 'success' => true]);

        Livewire::actingAs($admin)->test(Dashboard::class)
            ->call('navigate', 'system-logs')
            ->assertSee('Reset account password - System / unavailable operator')
            ->assertDontSee('Reset account password - OSCA Staff Synthetic Target');
    }

    public function test_operator_names_are_escaped_in_events_and_recent_activity(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create(['first_name' => '<script>synthetic</script>', 'last_name' => 'Operator']);
        AuditLog::create(['user_id' => $staff->id, 'action' => 'report.exported']);

        Livewire::actingAs($admin)->test(Dashboard::class)
            ->assertSee('OSCA Staff <script>synthetic</script> Operator')
            ->assertDontSee('<script>synthetic</script>', false)
            ->call('navigate', 'system-logs')
            ->assertSee('OSCA Staff <script>synthetic</script> Operator')
            ->assertDontSee('<script>synthetic</script>', false);
    }

    public function test_operator_lookup_has_a_fixed_query_count_for_a_full_page_of_events(): void
    {
        $admin = User::factory()->admin()->create();
        for ($index = 0; $index < 20; $index++) {
            AuditLog::create(['user_id' => $admin->id, 'action' => 'report.exported']);
        }
        $dashboard = new Dashboard;
        DB::enableQueryLog();
        DB::flushQueryLog();

        try {
            $events = $dashboard->filteredLogs();
            $recent = $dashboard->recentActivity();
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
        }

        $this->assertCount(15, $events->items());
        $this->assertCount(5, $recent);
        $this->assertCount(3, $queries);
    }
}
