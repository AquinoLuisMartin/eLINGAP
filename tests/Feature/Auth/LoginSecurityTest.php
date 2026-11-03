<?php

namespace Tests\Feature\Auth;

use App\Enums\LoginEvent;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_ends_the_session_when_audit_storage_fails(): void
    {
        $user = User::factory()->create();
        Log::spy();
        Event::listen('eloquent.creating: '.LoginLog::class, fn () => throw new RuntimeException('Synthetic audit failure'));

        $this->actingAs($user)->withSession(['sensitive-session-marker' => 'synthetic'])->post(route('logout'))
            ->assertRedirect(route('home'))->assertSessionMissing('sensitive-session-marker');

        $this->assertGuest();
        Log::shouldHaveReceived('warning')->once()->with('Logout audit could not be recorded.');
    }

    public function test_invalid_long_identities_return_a_validation_error_and_are_logged(): void
    {
        $identity = Str::lower(Str::random(100));

        $this->post(route('login'), ['employee_id' => $identity, 'password' => Str::password()])
            ->assertInvalid(['employee_id' => trans('auth.failed')]);

        $this->assertGuest();
        $this->assertDatabaseHas('login_logs', [
            'employee_id_attempted' => mb_substr($identity, 0, 100),
            'event' => LoginEvent::LoginFailed->value,
            'success' => false,
        ]);
    }

    public function test_inactive_accounts_cannot_log_in_with_valid_credentials(): void
    {
        $password = Str::password();
        $user = User::factory()->inactive()->create(['password_hash' => $password]);

        $this->post(route('login'), ['employee_id' => $user->employee_id, 'password' => $password])
            ->assertInvalid(['employee_id' => trans('auth.failed')]);

        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_employee_id_login_records_success(): void
    {
        $password = Str::password();
        $user = User::factory()->create(['password_hash' => $password]);

        $this->post(route('login'), ['employee_id' => Str::upper($user->employee_id), 'password' => $password])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('login_logs', ['user_id' => $user->id, 'event' => LoginEvent::Login->value, 'success' => true]);
    }

    public function test_login_accepts_the_checked_remember_me_value(): void
    {
        $password = Str::password();
        $user = User::factory()->create(['password_hash' => $password]);

        $response = $this->post(route('login'), ['employee_id' => $user->employee_id, 'password' => $password, 'remember' => '1']);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotEmpty($user->fresh()->remember_token);
    }

    public function test_login_uses_the_stored_employee_id_and_not_the_display_name(): void
    {
        $password = Str::password();
        $user = User::factory()->create(['first_name' => 'Synthetic', 'last_name' => 'Person', 'employee_id' => '2026-0002', 'password_hash' => $password]);

        $this->post(route('login'), ['employee_id' => 'SyntheticPerson', 'password' => $password])->assertInvalid('employee_id');
        $this->assertGuest();

        $this->post(route('login'), ['employee_id' => '2026-0002', 'password' => $password])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_preserves_hyphens_and_leading_zeros_in_employee_ids(): void
    {
        $password = Str::password();
        User::factory()->create(['employee_id' => '2026-0004', 'password_hash' => $password]);

        $this->post(route('login'), ['employee_id' => '20260004', 'password' => $password])->assertInvalid(['employee_id' => trans('auth.failed')]);
        $this->assertGuest();

        $this->post(route('login'), ['employee_id' => ' 2026-0004 ', 'password' => $password])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    #[TestWith(['synthetic  person'])]
    #[TestWith(['synthetic/person'])]
    #[TestWith(['synthetic@'])]
    #[TestWith(['synthetic@@example.com'])]
    public function test_unknown_legacy_identity_formats_have_generic_failures(string $identity): void
    {
        $this->post(route('login'), ['employee_id' => $identity, 'password' => Str::password()])->assertInvalid('employee_id');
        $this->assertGuest();
        $this->assertDatabaseCount('login_logs', 1);
    }

    #[TestWith([['2026-0002']])]
    #[TestWith([true])]
    #[TestWith([123])]
    public function test_login_rejects_non_string_identities_without_a_server_error(mixed $identity): void
    {
        $this->postJson(route('login'), ['employee_id' => $identity, 'password' => Str::password()])
            ->assertUnprocessable()->assertJsonValidationErrors('employee_id');

        $this->assertGuest();
    }

    public function test_repeated_failed_logins_are_throttled_until_the_lockout_expires(): void
    {
        $password = Str::password();
        $user = User::factory()->create(['password_hash' => $password]);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login'), ['employee_id' => $user->employee_id, 'password' => Str::password()])
                ->assertInvalid(['employee_id' => trans('auth.failed')]);
        }

        $this->post(route('login'), ['employee_id' => $user->employee_id, 'password' => $password])
            ->assertInvalid(['employee_id']);
        $this->assertGuest();

        $this->travel(61)->seconds();
        $this->post(route('login'), ['employee_id' => $user->employee_id, 'password' => $password])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }
}
