<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::query()->firstOrCreate(
            ['name' => UserRole::Admin->value],
            ['description' => 'System Administrator']
        );

        $staffRole = Role::query()->firstOrCreate(
            ['name' => UserRole::OscaStaff->value],
            ['description' => 'OSCA Staff']
        );

        $this->admin = User::query()->create([
            'role_id' => $adminRole->id,
            'username' => 'adminstamaria',
            'email' => 'adminstamaria@bulacan.gov.ph',
            'password_hash' => 'password123',
            'first_name' => 'Admin',
            'last_name' => 'StaMaria',
            'is_active' => true,
        ]);

        $this->staff = User::query()->create([
            'role_id' => $staffRole->id,
            'username' => 'stamariastaff',
            'email' => 'stamariastaff@bulacan.gov.ph',
            'password_hash' => 'password123',
            'first_name' => 'OSCA',
            'last_name' => 'Staff',
            'is_active' => true,
        ]);
    }

    public function test_admin_login_redirects_to_administration_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'adminstamaria@bulacan.gov.ph',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('administration.dashboard'));
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_staff_login_redirects_to_staff_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'stamariastaff@bulacan.gov.ph',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->staff);
    }

    public function test_admin_can_access_administration_dashboard(): void
    {
        $this->actingAs($this->admin)
            ->get(route('administration.dashboard'))
            ->assertOk();
    }

    public function test_staff_cannot_access_administration_dashboard(): void
    {
        $this->actingAs($this->staff)
            ->get(route('administration.dashboard'))
            ->assertForbidden();
    }

    public function test_staff_can_access_staff_dashboard(): void
    {
        $this->actingAs($this->staff)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_admin_cannot_access_staff_dashboard(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_staff_can_access_application_verify_route(): void
    {
        $this->actingAs($this->staff)
            ->get(route('applications.verify'))
            ->assertOk();
    }

    public function test_admin_can_login_with_username(): void
    {
        $response = $this->post('/login', [
            'email' => 'adminstamaria',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('administration.dashboard'));
        $this->assertAuthenticatedAs($this->admin);
    }
}
