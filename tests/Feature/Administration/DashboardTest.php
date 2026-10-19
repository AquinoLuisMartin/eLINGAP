<?php

namespace Tests\Feature\Administration;

use App\Livewire\Administration\Dashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->admin()->create();
    }

    public function test_administration_dashboard_screen_can_be_rendered(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/administration/dashboard');

        $response->assertStatus(200);
        $response->assertSeeLivewire(Dashboard::class);
    }

    public function test_dashboard_navigation_switches_tabs(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(Dashboard::class)
            ->assertSet('active', 'dashboard')
            ->call('navigate', 'records')
            ->assertSet('active', 'records')
            ->call('navigate', 'programs')
            ->assertSet('active', 'programs')
            ->call('navigate', 'sms')
            ->assertSet('active', 'sms')
            ->call('navigate', 'users')
            ->assertSet('active', 'users')
            ->call('navigate', 'system-logs')
            ->assertSet('active', 'system-logs')
            ->call('navigate', 'configuration')
            ->assertSet('active', 'configuration')
            ->call('navigate', 'help')
            ->assertSet('active', 'help');
    }

    public function test_dashboard_can_open_a_linked_module(): void
    {
        Livewire::actingAs($this->adminUser)
            ->withQueryParams(['tab' => 'configuration'])
            ->test(Dashboard::class)
            ->assertSet('active', 'configuration')
            ->assertSee('System Configuration');
    }

    public function test_dashboard_rejects_an_unknown_linked_module(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('administration.dashboard', ['tab' => 'unknown']))
            ->assertNotFound();
    }

    public function test_admin_registry_does_not_offer_senior_registration(): void
    {
        Livewire::actingAs($this->adminUser)->test(Dashboard::class)
            ->call('navigate', 'records')
            ->assertDontSeeHtml('href="'.route('senior-citizens.create').'"');
    }

    public function test_admin_help_links_to_registration_review_instead_of_a_forbidden_form(): void
    {
        Livewire::actingAs($this->adminUser)->test(Dashboard::class)
            ->call('navigate', 'help')
            ->assertSeeHtml('href="'.route('registrations.index').'"')
            ->assertDontSeeHtml('href="'.route('senior-citizens.create').'"');
    }

    public function test_programs_link_to_the_persisted_creation_workflow(): void
    {
        Livewire::actingAs($this->adminUser)->test(Dashboard::class)
            ->call('navigate', 'programs')
            ->assertSeeHtml('href="'.route('benefits.programs').'"');
    }

    public function test_can_toggle_user_status(): void
    {
        $user = User::factory()->inactive()->create();

        Livewire::actingAs($this->adminUser)
            ->test(Dashboard::class)
            ->call('toggleUserStatus', $user->id)
            ->assertSet('toast', $user->full_name.' is now active.');

        $this->assertTrue($user->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->adminUser->id, 'action' => 'user.access_changed', 'auditable_id' => $user->id]);
    }

    public function test_dispatcher_links_to_the_validated_broadcast_workflow(): void
    {
        Livewire::actingAs($this->adminUser)->test(Dashboard::class)
            ->call('navigate', 'sms')
            ->assertSeeHtml('href="'.route('sms.blasts.create').'"')
            ->assertDontSee('credits available');
    }

    public function test_can_toggle_theme(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(Dashboard::class)
            ->assertSet('theme', 'light')
            ->call('toggleTheme')
            ->assertSet('theme', 'dark');
    }
}
