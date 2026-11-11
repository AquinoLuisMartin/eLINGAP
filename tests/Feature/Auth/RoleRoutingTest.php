<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
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
            'employee_id' => $this->admin->employee_id,
            'password' => $this->password,
        ]);

        $response->assertRedirect(route('administration.dashboard'));
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_staff_login_redirects_to_staff_dashboard(): void
    {
        $response = $this->post('/login', [
            'employee_id' => $this->staff->employee_id,
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

    public function test_admin_dashboard_entry_redirects_to_administration(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('administration.dashboard'));
    }

    public function test_staff_can_access_application_verify_route(): void
    {
        $this->actingAs($this->staff)
            ->get(route('applications.verify'))
            ->assertRedirect(route('applications.index', ['status' => 'PENDING']));
    }

    public function test_admin_can_login_with_employee_id(): void
    {
        $response = $this->post('/login', [
            'employee_id' => $this->admin->employee_id,
            'password' => $this->password,
        ]);

        $response->assertRedirect(route('administration.dashboard'));
        $this->assertAuthenticatedAs($this->admin);
    }

    #[TestWith([true, 'administration.dashboard'])]
    #[TestWith([false, 'dashboard'])]
    public function test_accounts_without_email_can_login_with_their_assigned_employee_id_and_access_their_dashboard(bool $admin, string $dashboard): void
    {
        $user = $admin ? $this->admin : $this->staff;
        $user->update(['employee_id' => $admin ? '2026-9001' : '2026-9002', 'email' => null]);

        $this->post(route('login'), [
            'employee_id' => Str::upper($user->employee_id),
            'password' => $this->password,
        ])->assertRedirect(route($dashboard));

        $this->assertAuthenticatedAs($user);
        $this->get(route($dashboard))->assertOk();
    }

    public function test_logout_redirects_to_the_homepage(): void
    {
        $response = $this->actingAs($this->staff)->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }
}
