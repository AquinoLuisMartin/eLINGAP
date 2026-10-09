<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
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

        $this->post(route('login'), ['email' => $user->email, 'password' => $password])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $stored = $user->fresh()->password_hash;
        $this->assertSame('bcrypt', password_get_info($stored)['algoName']);
        $this->assertTrue(Hash::check($password, $stored));
    }

    public function test_recovery_returns_the_same_message_for_known_and_unknown_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $message = 'If an account matches that email address, a password reset link will be sent.';

        foreach ([$user->email, 'missing@example.test', $user->email] as $email) {
            $this->post(route('password.email'), ['email' => $email])
                ->assertRedirect()->assertSessionHas('status', $message);
        }

        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_recovery_requests_are_rate_limited_even_for_unknown_accounts(): void
    {
        Notification::fake();
        foreach (range(1, 5) as $attempt) {
            $this->post(route('password.email'), ['email' => 'missing'.$attempt.'@example.test'])->assertRedirect();
        }

        $this->post(route('password.email'), ['email' => 'missing6@example.test'])->assertStatus(429);
        $this->post(route('login'), ['email' => 'missing@example.test', 'password' => 'Synthetic-test-password'])
            ->assertInvalid(['email' => trans('auth.failed')]);
        Notification::assertNothingSent();
    }

    public function test_reset_validation_does_not_flash_tokens_or_passwords(): void
    {
        $this->post(route('password.update'), [
            'email' => 'missing@example.test',
            'token' => 'synthetic-token',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertInvalid(['password']);

        $this->assertNull(session()->getOldInput('token'));
        $this->assertNull(session()->getOldInput('password'));
        $this->assertNull(session()->getOldInput('password_confirmation'));
    }
}
