<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoleRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $staff;

    protected string $password;

    protected function setUp(): void
    {
        parent::setUp();

        $this->password = Str::password();
        $this->admin = User::factory()->admin()->create(['password_hash' => $this->password]);
        $this->staff = User::factory()->create(['password_hash' => $this->password]);
    }

    public function test_admin_login_redirects_to_administration_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => $this->admin->email,
            'password' => $this->password,
        ]);

        $response->assertRedirect(route('administration.dashboard'));
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_staff_login_redirects_to_staff_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => $this->staff->email,
            'password' => $this->password,
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
            'email' => $this->admin->username,
            'password' => $this->password,
        ]);

        $response->assertRedirect(route('administration.dashboard'));
        $this->assertAuthenticatedAs($this->admin);
    }
}
