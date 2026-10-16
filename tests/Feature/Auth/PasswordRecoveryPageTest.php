<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PasswordRecoveryPageTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['password.request', 'password.email'])]
    #[TestWith(['password.reset', 'password.update'])]
    public function test_malformed_email_input_does_not_break_the_validation_response(string $page, string $action): void
    {
        $url = route($page, $page === 'password.reset' ? ['token' => 'synthetic-token'] : []);

        $this->from($url)->post(route($action), ['email' => ['unexpected']])->assertSessionHasErrors('email');

        $this->withCookie(config('session.cookie'), session()->getId())
            ->get($url)->assertOk()->assertSee('aria-invalid="true"', false);
    }

    public function test_recovery_page_has_a_working_accessible_form(): void
    {
        $this->get(route('password.request'))
            ->assertSee('action="'.route('password.email').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="email"', false)
            ->assertSee('autocomplete="email"', false)
            ->assertSee('Send reset link');
    }

    public function test_reset_page_preserves_the_link_values_and_has_password_fields(): void
    {
        $this->get(route('password.reset', ['token' => 'synthetic-token', 'email' => 'synthetic@example.test']))
            ->assertSee('action="'.route('password.update').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="token" value="synthetic-token"', false)
            ->assertSee('value="synthetic@example.test"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('autocomplete="new-password"', false);
    }

    public function test_reset_page_does_not_crash_for_an_array_email(): void
    {
        $this->get(route('password.reset', ['token' => 'synthetic-token', 'email' => ['unexpected']]))
            ->assertOk()->assertSee('Reset password');
    }

    public function test_a_reset_token_changes_the_password_once_and_returns_to_login(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $payload = ['token' => $token, 'email' => $user->email, 'password' => 'Synthetic-new-password', 'password_confirmation' => 'Synthetic-new-password'];

        $this->post(route('password.update'), $payload)->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Synthetic-new-password', $user->fresh()->password_hash));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->post(route('password.update'), $payload)->assertSessionHasErrors('email');
    }
}
