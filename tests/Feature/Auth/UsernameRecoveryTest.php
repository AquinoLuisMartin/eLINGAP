<?php

namespace Tests\Feature\Auth;

use App\Enums\PasswordResetStatus;
use App\Models\AuditLog;
use App\Models\PasswordResetRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\PasswordRecovery;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class UsernameRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_username_login_ignores_email_and_upgrades_legacy_hashes(): void
    {
        $user = User::factory()->create(['username' => 'legacy_user']);
        DB::table('users')->where('id', $user->id)->update(['password_hash' => hash('sha256', 'Synthetic legacy password')]);
        $this->post(route('login'), ['username' => $user->email, 'password' => 'Synthetic legacy password'])->assertSessionHasErrors('username');
        $this->assertGuest();

        $this->post(route('login'), ['username' => 'LEGACY_USER', 'password' => 'Synthetic legacy password'])->assertRedirectToRoute('dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertSame('argon2id', password_get_info($user->fresh()->password_hash)['algoName']);
        $this->assertSame(1, session('credential_version'));
        $this->assertDatabaseHas('login_logs', ['user_id' => $user->id, 'event' => 'LOGIN']);
    }

    #[TestWith(['active'])]
    #[TestWith(['inactive'])]
    #[TestWith(['administrator'])]
    #[TestWith(['missing'])]
    public function test_forgot_password_confirmation_does_not_disclose_account_eligibility(string $kind): void
    {
        $user = match ($kind) {
            'inactive' => User::factory()->inactive()->create(),
            'administrator' => User::factory()->admin()->create(),
            'missing' => null,
            default => User::factory()->create(),
        };
        $before = $user?->password_hash;

        $this->post(route('password.request.store'), ['username' => $user?->username ?? 'missing.user'])
            ->assertRedirect()->assertSessionHas('status', PasswordRecovery::CONFIRMATION);

        $this->assertDatabaseCount('password_reset_requests', $kind === 'active' ? 1 : 0);
        if ($user) {
            $this->assertSame($before, $user->fresh()->password_hash);
        }
    }

    public function test_duplicate_requests_do_not_multiply_pending_or_approved_recovery(): void
    {
        $user = User::factory()->create();
        $service = app(PasswordRecovery::class);
        $request = $service->request($user->username);
        $this->assertSame($request->id, $service->request(strtoupper($user->username))->id);
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic admin password']);
        $service->review($request, $admin, 'approve', true, 'Synthetic admin password');

        $this->assertSame($request->id, $service->request($user->username)->id);
        $this->assertDatabaseCount('password_reset_requests', 1);
        $this->assertSame(1, AuditLog::where('action', 'user.recovery_requested')->count());
    }

    public function test_username_suggestions_normalize_names_and_add_numeric_suffixes(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['username' => 'mariaclara.delacruz']);
        $this->actingAs($admin)->postJson(route('administration.users.username-suggestion'),
            ['first_name' => 'María Clara', 'last_name' => 'De la Cruz'])->assertExactJson(['username' => 'mariaclara.delacruz2']);
        $this->postJson(route('administration.users.username-suggestion'), ['first_name' => '!!!', 'last_name' => '???'])
            ->assertExactJson(['username' => 'user.account']);
    }

    public function test_account_creation_generates_a_private_temporary_password_and_requires_a_change(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $response = $this->post(route('administration.users.store'), $this->account());
        $response->assertOk()->assertSee('Private credential handover');
        $credential = $response->viewData('credential');
        $user = User::where('username', 'synthetic.staff')->sole();

        $this->assertSame(24, strlen($credential));
        $this->assertTrue(Hash::check($credential, $user->password_hash));
        $this->assertTrue($user->must_change_password);
        $this->assertNull($user->email);
        $this->assertNull(session()->getOldInput('password'));
        $this->get(route('administration.users.edit', $user))->assertDontSee($credential);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created', 'auditable_id' => $user->id]);
    }

    public function test_json_requests_cannot_receive_or_create_temporary_credentials(): void
    {
        $this->actingAs(User::factory()->admin()->create())->postJson(route('administration.users.store'), $this->account())->assertStatus(406);

        $this->assertDatabaseCount('users', 1);
    }

    #[TestWith(['.leading'])]
    #[TestWith(['trailing.'])]
    #[TestWith(['double..period'])]
    #[TestWith(['with_space'])]
    #[TestWith(['with-dash'])]
    #[TestWith(['with space'])]
    #[TestWith(['abcd'])]
    public function test_new_username_formats_are_rejected(string $username): void
    {
        $this->actingAs(User::factory()->admin()->create())->post(route('administration.users.store'),
            [...$this->account(), 'username' => $username])->assertSessionHasErrors('username');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_case_insensitive_uniqueness_is_enforced_by_postgresql(): void
    {
        User::factory()->create(['username' => 'synthetic.staff']);
        try {
            DB::transaction(fn () => User::factory()->create(['username' => 'SYNTHETIC.STAFF']));
            $this->fail('The database must reject a case-insensitive duplicate.');
        } catch (UniqueConstraintViolationException $exception) {
            $this->assertSame('23505', (string) $exception->getCode());
        }
        $this->assertDatabaseCount('users', 1);
    }

    public function test_legacy_username_is_preserved_when_updating_staff_details(): void
    {
        $user = User::factory()->create(['username' => 'Legacy_User']);
        $this->actingAs(User::factory()->admin()->create())->put(route('administration.users.update', $user),
            [...$user->only(['role_id', 'first_name', 'last_name']), 'username' => 'legacy_user'])->assertSessionHasNoErrors();

        $this->assertSame('Legacy_User', $user->fresh()->username);
    }

    public function test_staff_management_cannot_create_promote_edit_or_recover_administrators(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        $staff = User::factory()->create();
        $this->actingAs($admin)->post(route('administration.users.store'), [...$this->account(), 'role_id' => $admin->role_id])->assertSessionHasErrors('role_id');
        $this->put(route('administration.users.update', $staff), [...$this->account(), 'role_id' => $admin->role_id])->assertSessionHasErrors('role_id');
        $this->get(route('administration.users.edit', $other))->assertForbidden();
        $this->post(route('administration.password-reset-requests.store', $other))->assertForbidden();
        $this->patch(route('administration.users.status.update', $other), ['is_active' => false])->assertForbidden();
    }

    public function test_temporary_login_is_restricted_until_current_password_is_changed(): void
    {
        $user = User::factory()->create(['password_hash' => 'Synthetic temporary password']);
        $user->forceFill(['must_change_password' => true])->save();
        $this->post(route('login'), ['username' => $user->username, 'password' => 'Synthetic temporary password'])->assertRedirectToRoute('password.change');
        $this->get(route('senior-citizens.index'))->assertRedirectToRoute('password.change');
        $this->post(route('password.change.update'), ['current_password' => 'wrong password',
            'password' => 'A new private staff passphrase', 'password_confirmation' => 'A new private staff passphrase'])->assertSessionHasErrors('current_password');
        $this->post(route('password.change.update'), ['current_password' => 'Synthetic temporary password',
            'password' => 'A new private staff passphrase', 'password_confirmation' => 'A new private staff passphrase'])->assertRedirectToRoute('dashboard');

        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertSame(2, $user->fresh()->credential_version);
        $this->assertSame(2, session('credential_version'));
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_approval_requires_current_admin_password_and_verified_identity(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic admin password']);
        $reset = app(PasswordRecovery::class)->request($user->username);
        $this->actingAs($admin)->patch(route('administration.password-reset-requests.update', $reset),
            ['decision' => 'approve', 'identity_verified' => true, 'admin_password' => 'incorrect password'])->assertSessionHasErrors('admin_password');
        $this->patch(route('administration.password-reset-requests.update', $reset),
            ['decision' => 'approve', 'admin_password' => 'Synthetic admin password'])->assertSessionHasErrors('identity_verified');

        $this->assertSame(PasswordResetStatus::Pending, $reset->fresh()->status);
        $this->assertNull($reset->fresh()->recovery_code_hash);
        $response = $this->patch(route('administration.password-reset-requests.update', $reset),
            ['decision' => 'approve', 'identity_verified' => true, 'admin_password' => 'Synthetic admin password'])->assertOk();
        $code = $response->viewData('credential');
        $this->assertSame(43, strlen($code));
        $this->assertTrue(Hash::check($code, $reset->fresh()->recovery_code_hash));
        $this->get(route('administration.password-reset-requests.show', $reset))->assertDontSee($code);
        $this->patch(route('administration.password-reset-requests.update', $reset),
            ['decision' => 'approve', 'identity_verified' => true, 'admin_password' => 'Synthetic admin password'])->assertStatus(409);
        $this->assertNull(session()->getOldInput('admin_password'));
    }

    public function test_recovery_completes_once_and_revokes_old_sessions_and_tokens(): void
    {
        [$user, $reset, $code] = $this->approved();
        $user->forceFill(['remember_token' => 'synthetic-persistent-token'])->save();
        $payload = ['username' => $user->username, 'recovery_code' => $code,
            'password' => 'A replacement personal passphrase', 'password_confirmation' => 'A replacement personal passphrase'];

        $this->post(route('password.update'), $payload)->assertRedirectToRoute('login');

        $this->assertGuest();
        $this->assertTrue(Hash::check('A replacement personal passphrase', $user->fresh()->password_hash));
        $this->assertSame(2, $user->fresh()->credential_version);
        $this->assertNull($user->fresh()->remember_token);
        $this->assertSame(PasswordResetStatus::Completed, $reset->fresh()->status);
        $this->assertNull($reset->fresh()->recovery_code_hash);
        $this->post(route('password.update'), $payload)->assertSessionHasErrors('recovery_code');
        $this->assertSame(1, AuditLog::where('action', 'user.recovery_completed')->count());
        $this->actingAs($user->fresh())->withSession(['credential_version' => 1])->get(route('dashboard'))->assertRedirectToRoute('login');
    }

    public function test_incorrect_codes_consume_attempts_and_fifth_failure_expires_the_code(): void
    {
        [$user, $reset, $code] = $this->approved();
        $service = app(PasswordRecovery::class);
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->assertFalse($service->complete($user->username, str_repeat('x', 43), 'A replacement personal passphrase'));
            $this->assertSame($attempt, $reset->fresh()->failed_attempts);
        }

        $this->assertSame(PasswordResetStatus::Expired, $reset->fresh()->status);
        $this->assertNull($reset->fresh()->recovery_code_hash);
        $this->assertFalse($service->complete($user->username, $code, 'A replacement personal passphrase'));
        $this->assertSame(1, $user->fresh()->credential_version);
    }

    public function test_expiry_at_fifteen_minutes_requires_a_new_reviewed_request(): void
    {
        $this->freezeTime();
        [$user, $reset, $code] = $this->approved();
        $this->travel(15)->minutes();

        $this->assertFalse(app(PasswordRecovery::class)->complete($user->username, $code, 'A replacement personal passphrase'));
        $new = app(PasswordRecovery::class)->request($user->username);
        $this->assertNotSame($reset->id, $new->id);
        $this->assertSame(PasswordResetStatus::Pending, $new->status);
    }

    public function test_recovery_rolls_back_if_audit_recording_fails(): void
    {
        [$user, $reset, $code] = $this->approved();
        $old = $user->password_hash;
        Exceptions::fake();
        Event::listen('eloquent.creating: '.AuditLog::class, fn () => throw new RuntimeException('Synthetic audit failure'));

        $this->post(route('password.update'), ['username' => $user->username, 'recovery_code' => $code,
            'password' => 'A replacement personal passphrase', 'password_confirmation' => 'A replacement personal passphrase'])->assertInternalServerError();

        $this->assertSame($old, $user->fresh()->password_hash);
        $this->assertSame(PasswordResetStatus::Approved, $reset->fresh()->status);
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_recovery_request_throttles_unknown_identifiers_without_requiring_email(): void
    {
        foreach (range(1, 3) as $attempt) {
            $this->post(route('password.request.store'), ['username' => 'missing.user'])->assertRedirect();
        }
        $this->post(route('password.request.store'), ['username' => 'missing.user'])->assertStatus(429);
        $this->assertDatabaseCount('password_reset_requests', 0);
    }

    private function account(): array
    {
        $role = Role::firstOrCreate(['name' => 'OSCA_STAFF']);

        return ['first_name' => 'Synthetic', 'last_name' => 'Staff', 'username' => 'synthetic.staff', 'role_id' => $role->id, 'is_active' => true];
    }

    public function test_synthetic_employee_handover_covers_provisioning_first_login_and_verified_recovery(): void
    {
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic administrator passphrase']);
        $response = $this->actingAs($admin)->post(route('administration.users.store'), $this->account())->assertOk();
        $temporary = $response->viewData('credential');
        $this->post(route('logout'))->assertRedirectToRoute('home');
        $this->post(route('login'), ['username' => 'synthetic.staff', 'password' => $temporary])->assertRedirectToRoute('password.change');
        $this->get(route('reports.index'))->assertRedirectToRoute('password.change');
        $this->post(route('password.change.update'), ['current_password' => $temporary,
            'password' => 'Synthetic employee personal passphrase', 'password_confirmation' => 'Synthetic employee personal passphrase'])->assertRedirectToRoute('dashboard');
        $this->post(route('logout'))->assertRedirectToRoute('home');
        $this->post(route('password.request.store'), ['username' => 'synthetic.staff'])->assertSessionHas('status', PasswordRecovery::CONFIRMATION);
        $this->post(route('login'), ['username' => $admin->username, 'password' => 'Synthetic administrator passphrase'])->assertRedirectToRoute('administration.dashboard');
        $reset = PasswordResetRequest::sole();
        $this->get(route('administration.password-reset-requests.index'))->assertSee('synthetic.staff')->assertSee('PENDING');
        $approval = $this->patch(route('administration.password-reset-requests.update', $reset),
            ['decision' => 'approve', 'identity_verified' => true, 'admin_password' => 'Synthetic administrator passphrase'])->assertOk();
        $code = $approval->viewData('credential');
        $this->post(route('logout'))->assertRedirectToRoute('home');
        $this->post(route('password.update'), ['username' => 'synthetic.staff', 'recovery_code' => $code,
            'password' => 'Synthetic employee recovered passphrase', 'password_confirmation' => 'Synthetic employee recovered passphrase'])->assertRedirectToRoute('login');
        $this->post(route('login'), ['username' => 'synthetic.staff', 'password' => 'Synthetic employee recovered passphrase'])->assertRedirectToRoute('dashboard');

        $this->get(route('reports.index'))->assertOk();
        $this->assertSame(PasswordResetStatus::Completed, $reset->fresh()->status);
        $this->assertDatabaseCount('users', 2);
    }

    private function approved(): array
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic admin password']);
        $service = app(PasswordRecovery::class);
        $reset = $service->request($user->username);
        $code = $service->review($reset, $admin, 'approve', true, 'Synthetic admin password');

        return [$user, $reset, $code];
    }
}
