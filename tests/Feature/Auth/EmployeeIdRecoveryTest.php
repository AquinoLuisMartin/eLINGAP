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

class EmployeeIdRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_id_login_ignores_email_and_upgrades_legacy_hashes(): void
    {
        $user = User::factory()->create(['employee_id' => '2026-0003']);
        DB::table('users')->where('id', $user->id)->update(['password_hash' => hash('sha256', 'Synthetic legacy password')]);
        $this->post(route('login'), ['employee_id' => $user->email, 'password' => 'Synthetic legacy password'])->assertSessionHasErrors('employee_id');
        $this->assertGuest();

        $this->post(route('login'), ['employee_id' => '2026-0003', 'password' => 'Synthetic legacy password'])->assertRedirectToRoute('dashboard');

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

        $this->post(route('password.request.store'), ['employee_id' => $user?->employee_id ?? 'missing.user'])
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
        $request = $service->request($user->employee_id);
        $this->assertSame($request->id, $service->request(strtoupper($user->employee_id))->id);
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic admin password']);
        $service->review($request, $admin, 'approve', true, 'Synthetic admin password');

        $this->assertSame($request->id, $service->request($user->employee_id)->id);
        $this->assertDatabaseCount('password_reset_requests', 1);
        $this->assertSame(1, AuditLog::where('action', 'user.recovery_requested')->count());
    }

    public function test_account_creation_requires_an_official_employee_id_instead_of_generating_one_from_names(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('administration.users.store'), [...$this->account(), 'employee_id' => ''])
            ->assertSessionHasErrors('employee_id');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_account_creation_generates_a_private_temporary_password_and_requires_a_change(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $response = $this->post(route('administration.users.store'), $this->account());
        $response->assertOk()->assertSee('Private credential handover');
        $credential = $response->viewData('credential');
        $user = User::where('employee_id', '2026-0001')->sole();

        $this->assertSame(15, strlen($credential));
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
    #[TestWith(['2026-001'])]
    #[TestWith(['2026-00010'])]
    #[TestWith(['20260001'])]
    #[TestWith(['2026/0001'])]
    #[TestWith(['2026-0001x'])]
    public function test_new_employee_id_formats_are_rejected(string $employeeId): void
    {
        $this->actingAs(User::factory()->admin()->create())->post(route('administration.users.store'),
            [...$this->account(), 'employee_id' => $employeeId])->assertSessionHasErrors([
                'employee_id' => 'Enter the official employee ID in YYYY-NNNN format, for example 2026-0001.',
            ]);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_employee_id_uniqueness_is_enforced_by_postgresql(): void
    {
        User::factory()->create(['employee_id' => '2026-0001']);
        try {
            DB::transaction(fn () => User::factory()->create(['employee_id' => '2026-0001']));
            $this->fail('The database must reject a case-insensitive duplicate.');
        } catch (UniqueConstraintViolationException $exception) {
            $this->assertSame('23505', (string) $exception->getCode());
        }
        $this->assertDatabaseCount('users', 1);
    }

    public function test_employee_id_is_preserved_when_updating_staff_details(): void
    {
        $user = User::factory()->create(['employee_id' => '2026-0003']);
        $this->actingAs(User::factory()->admin()->create())->put(route('administration.users.update', $user),
            [...$user->only(['role_id', 'first_name', 'last_name']), 'employee_id' => '2026-0003'])->assertSessionHasNoErrors();

        $this->assertSame('2026-0003', $user->fresh()->employee_id);
    }

    public function test_duplicate_employee_ids_are_rejected_when_creating_or_editing_accounts(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $existing = User::factory()->create(['employee_id' => '2026-0001']);
        $other = User::factory()->create(['employee_id' => '2026-0002']);

        $this->post(route('administration.users.store'), $this->account())->assertSessionHasErrors([
            'employee_id' => 'This employee ID is already assigned to another account.',
        ]);
        $this->put(route('administration.users.update', $other), [...$this->account(), 'employee_id' => $existing->employee_id])
            ->assertSessionHasErrors('employee_id');

        $this->assertSame('2026-0002', $other->fresh()->employee_id);
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_changing_an_employee_id_revokes_previous_sessions_and_recovery_codes(): void
    {
        $user = User::factory()->create(['employee_id' => '2026-0001', 'remember_token' => 'synthetic-token']);
        $reset = app(PasswordRecovery::class)->request($user->employee_id);
        $admin = User::factory()->admin()->create();
        $passwordHash = $user->password_hash;
        config(['session.driver' => 'database']);
        DB::table('sessions')->insert(['id' => 'synthetic-staff-session', 'user_id' => $user->id, 'payload' => 'synthetic', 'last_activity' => time()]);

        $this->actingAs($admin)->put(route('administration.users.update', $user), [...$this->account(), 'employee_id' => '2026-0002'])
            ->assertRedirectToRoute('administration.users.index')->assertSessionHasNoErrors();

        $this->assertSame('2026-0002', $user->fresh()->employee_id);
        $this->assertSame($passwordHash, $user->fresh()->password_hash);
        $this->assertSame(2, $user->fresh()->credential_version);
        $this->assertNull($user->fresh()->remember_token);
        $this->assertSame(PasswordResetStatus::Expired, $reset->fresh()->status);
        $this->assertDatabaseMissing('sessions', ['id' => 'synthetic-staff-session']);
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
        $this->post(route('login'), ['employee_id' => $user->employee_id, 'password' => 'Synthetic temporary password'])->assertRedirectToRoute('password.change');
        $this->get(route('senior-citizens.index'))->assertRedirectToRoute('password.change');
        $this->post(route('password.change.update'), ['current_password' => 'wrong password',
            'password' => 'Private-test!42', 'password_confirmation' => 'Private-test!42'])->assertSessionHasErrors('current_password');
        $this->post(route('password.change.update'), ['current_password' => 'Synthetic temporary password',
            'password' => 'Private-test!42', 'password_confirmation' => 'Private-test!42'])->assertRedirectToRoute('dashboard');

        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertSame(2, $user->fresh()->credential_version);
        $this->assertSame(2, session('credential_version'));
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_approval_requires_current_admin_password_and_verified_identity(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic admin password']);
        $reset = app(PasswordRecovery::class)->request($user->employee_id);
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
        $payload = ['employee_id' => $user->employee_id, 'recovery_code' => $code,
            'password' => 'Replace-test!42', 'password_confirmation' => 'Replace-test!42'];

        $this->post(route('password.update'), $payload)->assertRedirectToRoute('login');

        $this->assertGuest();
        $this->assertTrue(Hash::check('Replace-test!42', $user->fresh()->password_hash));
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
            $this->assertFalse($service->complete($user->employee_id, str_repeat('x', 43), 'Replace-test!42'));
            $this->assertSame($attempt, $reset->fresh()->failed_attempts);
        }

        $this->assertSame(PasswordResetStatus::Expired, $reset->fresh()->status);
        $this->assertNull($reset->fresh()->recovery_code_hash);
        $this->assertFalse($service->complete($user->employee_id, $code, 'Replace-test!42'));
        $this->assertSame(1, $user->fresh()->credential_version);
    }

    public function test_expiry_at_fifteen_minutes_requires_a_new_reviewed_request(): void
    {
        $this->freezeTime();
        [$user, $reset, $code] = $this->approved();
        $this->travel(15)->minutes();

        $this->assertFalse(app(PasswordRecovery::class)->complete($user->employee_id, $code, 'Replace-test!42'));
        $new = app(PasswordRecovery::class)->request($user->employee_id);
        $this->assertNotSame($reset->id, $new->id);
        $this->assertSame(PasswordResetStatus::Pending, $new->status);
    }

    public function test_recovery_rolls_back_if_audit_recording_fails(): void
    {
        [$user, $reset, $code] = $this->approved();
        $old = $user->password_hash;
        Exceptions::fake();
        Event::listen('eloquent.creating: '.AuditLog::class, fn () => throw new RuntimeException('Synthetic audit failure'));

        $this->post(route('password.update'), ['employee_id' => $user->employee_id, 'recovery_code' => $code,
            'password' => 'Replace-test!42', 'password_confirmation' => 'Replace-test!42'])->assertInternalServerError();

        $this->assertSame($old, $user->fresh()->password_hash);
        $this->assertSame(PasswordResetStatus::Approved, $reset->fresh()->status);
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_recovery_request_throttles_unknown_identifiers_without_requiring_email(): void
    {
        foreach (range(1, 3) as $attempt) {
            $this->post(route('password.request.store'), ['employee_id' => 'missing.user'])->assertRedirect();
        }
        $this->post(route('password.request.store'), ['employee_id' => 'missing.user'])->assertStatus(429);
        $this->assertDatabaseCount('password_reset_requests', 0);
    }

    private function account(): array
    {
        $role = Role::firstOrCreate(['name' => 'OSCA_STAFF']);

        return ['first_name' => 'Synthetic', 'last_name' => 'Staff', 'employee_id' => '2026-0001', 'role_id' => $role->id, 'is_active' => true];
    }

    public function test_synthetic_employee_handover_covers_provisioning_first_login_and_verified_recovery(): void
    {
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic administrator passphrase']);
        $response = $this->actingAs($admin)->post(route('administration.users.store'), $this->account())->assertOk();
        $temporary = $response->viewData('credential');
        $this->post(route('logout'))->assertRedirectToRoute('home');
        $this->post(route('login'), ['employee_id' => '2026-0001', 'password' => $temporary])->assertRedirectToRoute('password.change');
        $this->get(route('reports.index'))->assertRedirectToRoute('password.change');
        $this->post(route('password.change.update'), ['current_password' => $temporary,
            'password' => 'Personal-test42', 'password_confirmation' => 'Personal-test42'])->assertRedirectToRoute('dashboard');
        $this->post(route('logout'))->assertRedirectToRoute('home');
        $this->post(route('password.request.store'), ['employee_id' => '2026-0001'])->assertSessionHas('status', PasswordRecovery::CONFIRMATION);
        $this->post(route('login'), ['employee_id' => $admin->employee_id, 'password' => 'Synthetic administrator passphrase'])->assertRedirectToRoute('administration.dashboard');
        $reset = PasswordResetRequest::sole();
        $this->get(route('administration.password-reset-requests.index'))->assertSee('2026-0001')->assertSee('Pending');
        $approval = $this->patch(route('administration.password-reset-requests.update', $reset),
            ['decision' => 'approve', 'identity_verified' => true, 'admin_password' => 'Synthetic administrator passphrase'])->assertOk();
        $code = $approval->viewData('credential');
        $this->post(route('logout'))->assertRedirectToRoute('home');
        $this->post(route('password.update'), ['employee_id' => '2026-0001', 'recovery_code' => $code,
            'password' => 'Recover-test!42', 'password_confirmation' => 'Recover-test!42'])->assertRedirectToRoute('login');
        $this->post(route('login'), ['employee_id' => '2026-0001', 'password' => 'Recover-test!42'])->assertRedirectToRoute('dashboard');

        $this->get(route('reports.index'))->assertOk();
        $this->assertSame(PasswordResetStatus::Completed, $reset->fresh()->status);
        $this->assertDatabaseCount('users', 2);
    }

    private function approved(): array
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic admin password']);
        $service = app(PasswordRecovery::class);
        $reset = $service->request($user->employee_id);
        $code = $service->review($reset, $admin, 'approve', true, 'Synthetic admin password');

        return [$user, $reset, $code];
    }
}
