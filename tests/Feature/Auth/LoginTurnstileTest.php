<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LoginTurnstileTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.turnstile.site_key' => 'synthetic-site',
            'services.turnstile.secret_key' => 'synthetic-secret',
            'services.turnstile.action' => 'login',
            'services.turnstile.hostnames' => ['elingap.app', 'e-lingap.vercel.app'],
        ]);
    }

    #[TestWith(['elingap.app'])]
    #[TestWith(['e-lingap.vercel.app'])]
    public function test_verified_login_authenticates_on_an_allowed_hostname(string $hostname): void
    {
        $user = User::factory()->create(['password_hash' => 'Synthetic-login!42']);
        Http::preventStrayRequests();
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true, 'hostname' => $hostname, 'action' => 'login',
            ]),
        ]);

        $this->post(route('login'), $this->payload($user))->assertRedirectToRoute('dashboard');

        $this->assertAuthenticatedAs($user);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request['secret'] === 'synthetic-secret'
            && $request['response'] === 'synthetic-token');
    }

    #[TestWith([['success' => false, 'error-codes' => ['invalid-input-response']], 200])]
    #[TestWith([['success' => false, 'error-codes' => ['timeout-or-duplicate']], 200])]
    #[TestWith([['success' => true, 'hostname' => 'attacker.example', 'action' => 'login'], 200])]
    #[TestWith([['success' => true, 'hostname' => 'elingap.app', 'action' => 'register'], 200])]
    #[TestWith([['success' => true, 'hostname' => 'elingap.app'], 200])]
    #[TestWith([['success' => true, 'action' => 'login'], 200])]
    #[TestWith([['success' => 'true', 'hostname' => 'elingap.app', 'action' => 'login'], 200])]
    #[TestWith([['success' => true, 'hostname' => 'elingap.app', 'action' => 'login'], 503])]
    #[TestWith(['not-json', 200])]
    public function test_failed_verification_blocks_valid_credentials(array|string $body, int $status): void
    {
        $user = User::factory()->create(['password_hash' => 'Synthetic-login!42']);
        Http::preventStrayRequests();
        Http::fake(['https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response($body, $status)]);

        $this->from(route('login'))->post(route('login'), $this->payload($user))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['cf-turnstile-response' => 'Security verification failed. Please try again.'])
            ->assertSessionMissing('_old_input.cf-turnstile-response');

        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);
        $this->assertDatabaseCount('login_logs', 0);
        Http::assertSentCount(1);
    }

    public function test_connection_failure_blocks_login_with_a_retry_message(): void
    {
        $user = User::factory()->create(['password_hash' => 'Synthetic-login!42']);
        Http::preventStrayRequests();
        Http::fake(['https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::failedConnection()]);

        $this->post(route('login'), $this->payload($user))
            ->assertSessionHasErrors(['cf-turnstile-response' => 'Security verification failed. Please try again.']);

        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);
        $this->assertDatabaseCount('login_logs', 0);
    }

    #[TestWith([null, 'Complete the security verification before signing in.'])]
    #[TestWith([['invalid'], 'Security verification is invalid. Please try again.'])]
    public function test_invalid_token_input_is_rejected_without_contacting_cloudflare(mixed $token, string $message): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([])]);

        $this->post(route('login'), [
            'employee_id' => '2026-0001', 'password' => 'Synthetic-login!42', 'cf-turnstile-response' => $token,
        ])->assertSessionHasErrors(['cf-turnstile-response' => $message]);

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_oversized_token_is_rejected_without_contacting_cloudflare(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([])]);

        $this->post(route('login'), [
            'employee_id' => '2026-0001', 'password' => 'Synthetic-login!42', 'cf-turnstile-response' => str_repeat('x', 2049),
        ])->assertSessionHasErrors(['cf-turnstile-response' => 'Security verification is invalid. Please try again.']);

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_missing_secret_blocks_login_without_an_outbound_request(): void
    {
        config(['services.turnstile.secret_key' => null]);
        Http::preventStrayRequests();
        Http::fake(['https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([])]);

        $this->post(route('login'), [
            'employee_id' => '2026-0001', 'password' => 'Synthetic-login!42', 'cf-turnstile-response' => 'synthetic-token',
        ])->assertSessionHasErrors('cf-turnstile-response');

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_locked_account_does_not_contact_cloudflare(): void
    {
        $key = 'login:'.hash_hmac('sha256', '2026-0001', config('app.key'));
        RateLimiter::increment($key, amount: 5);
        Http::preventStrayRequests();
        Http::fake(['https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([])]);

        $this->post(route('login'), [
            'employee_id' => '2026-0001', 'password' => 'Synthetic-login!42', 'cf-turnstile-response' => 'synthetic-token',
        ])->assertSessionHasErrors('employee_id');

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_login_page_renders_only_the_public_site_key(): void
    {
        $this->get(route('login'))->assertSee('id="login-turnstile"', false)
            ->assertSee('data-sitekey="synthetic-site"', false)->assertDontSee('synthetic-secret');
    }

    public function test_local_dummy_response_is_accepted_with_explicit_local_overrides(): void
    {
        config(['services.turnstile.hostnames' => ['example.com'], 'services.turnstile.action' => null]);
        $user = User::factory()->create(['password_hash' => 'Synthetic-login!42']);
        Http::preventStrayRequests();
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true, 'hostname' => 'example.com',
            ]),
        ]);

        $this->post(route('login'), $this->payload($user))->assertRedirectToRoute('dashboard');

        $this->assertAuthenticatedAs($user);
        Http::assertSentCount(1);
    }

    private function payload(User $user): array
    {
        return ['employee_id' => $user->employee_id, 'password' => 'Synthetic-login!42', 'cf-turnstile-response' => 'synthetic-token'];
    }
}
