<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\PasswordRecovery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PasswordRecoveryPageTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['password.request', 'password.request.store'])]
    #[TestWith(['password.reset', 'password.update'])]
    public function test_malformed_username_input_does_not_break_the_validation_response(string $page, string $action): void
    {
        $url = route($page);
        $this->from($url)->post(route($action), ['username' => ['unexpected']])->assertSessionHasErrors('username');

        $this->withCookie(config('session.cookie'), session()->getId())->get($url)->assertOk()->assertSee('role="alert"', false);
    }

    public function test_recovery_page_has_a_working_accessible_form(): void
    {
        $this->get(route('password.request'))->assertSee('action="'.route('password.request.store').'"', false)
            ->assertSee('name="_token"', false)->assertSee('name="username"', false)
            ->assertSee('autocomplete="username"', false)->assertSee('Submit request');
    }

    public function test_reset_page_has_code_and_password_fields_without_url_tokens(): void
    {
        $this->get(route('password.reset'))->assertSee('action="'.route('password.update').'"', false)
            ->assertSee('name="_token"', false)->assertSee('name="recovery_code"', false)
            ->assertDontSee('name="token"', false)->assertDontSee('name="email"', false)
            ->assertSee('name="password"', false)->assertSee('name="password_confirmation"', false)
            ->assertSee('autocomplete="new-password"', false);
    }

    public function test_reset_page_ignores_malformed_legacy_email_query_parameters(): void
    {
        $this->get(route('password.reset', ['email' => ['unexpected']]))->assertOk()->assertSee('Complete password recovery');
    }

    public function test_a_recovery_code_changes_the_password_once_and_returns_to_login(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic administrator password']);
        $service = app(PasswordRecovery::class);
        $reset = $service->request($user->username);
        $code = $service->review($reset, $admin, 'approve', true, 'Synthetic administrator password');
        $payload = ['username' => $user->username, 'recovery_code' => $code,
            'password' => 'Synthetic-new-password', 'password_confirmation' => 'Synthetic-new-password'];

        $this->post(route('password.update'), $payload)->assertRedirectToRoute('login');

        $this->assertTrue(Hash::check('Synthetic-new-password', $user->fresh()->password_hash));
        $this->assertNull($reset->fresh()->recovery_code_hash);
        $this->post(route('password.update'), $payload)->assertSessionHasErrors('recovery_code');
    }
}
