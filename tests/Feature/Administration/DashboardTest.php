<?php

namespace Tests\Feature\Administration;

use App\Livewire\Administration\Dashboard;
use App\Models\Role;
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

        $role = Role::firstOrCreate(
            ['name' => 'ADMIN'],
            ['description' => 'System Administrator']
        );

        $this->adminUser = User::create([
            'role_id' => $role->id,
            'username' => 'admin',
            'email' => 'admin@elingap.gov.ph',
            'password_hash' => bcrypt('password123'),
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'is_active' => true,
        ]);
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

    public function test_can_save_new_senior_record(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(Dashboard::class)
            ->call('openModal', 'senior')
            ->assertSet('modal', 'senior')
            ->set('seniorForm.name', 'Juan Dela Cruz')
            ->set('seniorForm.age', '65')
            ->set('seniorForm.barangay', 'Poblacion')
            ->call('saveSenior')
            ->assertSet('modal', null)
            ->assertSet('toast', 'Senior record saved successfully.');
    }

    public function test_can_save_new_program(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(Dashboard::class)
            ->call('openModal', 'program')
            ->assertSet('modal', 'program')
            ->set('programForm.name', 'Centenarian Gift Award')
            ->set('programForm.agency', 'MSWDO')
            ->set('programForm.budget', '₱500,000')
            ->set('programForm.cycle', 'Annual 2027')
            ->call('saveProgram')
            ->assertSet('modal', null)
            ->assertSet('toast', 'Program saved successfully.');
    }

    public function test_can_toggle_user_status(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(Dashboard::class)
            ->call('toggleUserStatus', 'joseph.delacruz@osca.gov.ph')
            ->assertSet('toast', 'Joseph Dela Cruz is now active.');
    }

    public function test_can_schedule_broadcast_and_deduct_credits(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(Dashboard::class)
            ->set('smsBarangay', 'Poblacion')
            ->set('smsMessage', 'Paalala: Magdala ng valid ID bukas.')
            ->call('scheduleBroadcast')
            ->assertSet('toast', 'Broadcast scheduled successfully.')
            ->assertSet('credits', 12840 - 2418);
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
