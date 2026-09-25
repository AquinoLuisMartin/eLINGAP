<?php

namespace Tests\Feature\Auth;

use App\Enums\LoginEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_long_identities_return_a_validation_error_and_are_logged(): void
    {
        $identity = Str::lower(Str::random(255));

        $this->post(route('login'), ['email' => $identity, 'password' => Str::password()])
            ->assertInvalid(['email' => trans('auth.failed')]);

        $this->assertGuest();
        $this->assertDatabaseHas('login_logs', [
            'username_attempted' => mb_substr($identity, 0, 100),
            'event' => LoginEvent::LoginFailed->value,
            'success' => false,
        ]);
    }

    public function test_inactive_accounts_cannot_log_in_with_valid_credentials(): void
    {
        $password = Str::password();
        $user = User::factory()->inactive()->create(['password_hash' => $password]);

        $this->post(route('login'), ['email' => $user->email, 'password' => $password])
            ->assertInvalid(['email' => trans('auth.failed')]);

        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_login_remains_case_insensitive_and_records_success(): void
    {
        $password = Str::password();
        $user = User::factory()->create(['password_hash' => $password]);

        $this->post(route('login'), ['email' => Str::upper($user->email), 'password' => $password])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('login_logs', ['user_id' => $user->id, 'event' => LoginEvent::Login->value, 'success' => true]);
    }

    public function test_repeated_failed_logins_are_throttled_until_the_lockout_expires(): void
    {
        $password = Str::password();
        $user = User::factory()->create(['password_hash' => $password]);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login'), ['email' => $user->email, 'password' => Str::password()])
                ->assertInvalid(['email' => trans('auth.failed')]);
        }

        $this->post(route('login'), ['email' => $user->email, 'password' => $password])
            ->assertInvalid(['email']);
        $this->assertGuest();

        $this->travel(61)->seconds();
        $this->post(route('login'), ['email' => $user->email, 'password' => $password])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }
}
