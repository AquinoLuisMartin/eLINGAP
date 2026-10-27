<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\PasswordRecovery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordRecoverySecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_successful_legacy_login_upgrades_the_stored_hash(): void
    {
        $password = 'Synthetic-legacy-password';
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update(['password_hash' => hash('sha256', $password)]);

        $this->post(route('login'), ['username' => $user->username, 'password' => $password])->assertRedirectToRoute('dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertSame('argon2id', password_get_info($user->fresh()->password_hash)['algoName']);
        $this->assertTrue(Hash::check($password, $user->fresh()->password_hash));
    }

    public function test_recovery_returns_the_same_message_for_known_and_unknown_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        foreach ([$user->username, 'missing.user', $user->username] as $username) {
            $this->post(route('password.request.store'), ['username' => $username])
                ->assertRedirect()->assertSessionHas('status', PasswordRecovery::CONFIRMATION);
        }

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_requests', 1);
    }

    public function test_recovery_requests_are_rate_limited_even_for_unknown_accounts(): void
    {
        Notification::fake();
        foreach (range(1, 5) as $attempt) {
            $this->post(route('password.request.store'), ['username' => 'missing.user'.$attempt])->assertRedirect();
        }
        $this->post(route('password.request.store'), ['username' => 'missing.user6'])->assertStatus(429);
        $this->post(route('login'), ['username' => 'missing.user', 'password' => 'Synthetic-test-password'])
            ->assertInvalid(['username' => trans('auth.failed')]);
        Notification::assertNothingSent();
    }

    public function test_reset_validation_does_not_flash_codes_or_passwords(): void
    {
        $this->post(route('password.update'), ['username' => 'missing.user', 'recovery_code' => 'synthetic-code',
            'password' => ['malformed'], 'password_confirmation' => 'short'])->assertInvalid('password');

        $this->assertNull(session()->getOldInput('recovery_code'));
        $this->assertNull(session()->getOldInput('password'));
        $this->assertNull(session()->getOldInput('password_confirmation'));
    }
}
