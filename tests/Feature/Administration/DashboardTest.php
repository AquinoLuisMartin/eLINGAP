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

    public function test_registry_links_to_the_persisted_creation_workflow(): void
    {
        Livewire::actingAs($this->adminUser)->test(Dashboard::class)
            ->call('navigate', 'records')
            ->assertSeeHtml('href="'.route('senior-citizens.create').'"');
    }

    public function test_programs_link_to_the_persisted_creation_workflow(): void
    {
        Livewire::actingAs($this->adminUser)->test(Dashboard::class)
            ->call('navigate', 'programs')
            ->assertSeeHtml('href="'.route('administration.programs.create').'"');
    }

    public function test_can_toggle_user_status(): void
    {
        $user = User::factory()->inactive()->create();

        Livewire::actingAs($this->adminUser)
            ->test(Dashboard::class)
            ->call('toggleUserStatus', $user->id)
            ->assertSet('toast', $user->full_name.' is now active.');

        $this->assertTrue($user->fresh()->is_active);
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
