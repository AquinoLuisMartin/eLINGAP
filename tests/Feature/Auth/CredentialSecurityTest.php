<?php

namespace Tests\Feature\Auth;

use App\Enums\PasswordResetStatus;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Auth\PasswordRecovery;
use App\Services\Auth\Username;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Drawer\Utils;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class CredentialSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['short'])]
    #[TestWith(['long'])]
    #[TestWith(['null'])]
    #[TestWith(['reuse'])]
    #[TestWith(['identifier'])]
    public function test_password_policy_rejects_invalid_replacements_without_changing_credentials(string $case): void
    {
        $user = User::factory()->create(['username' => 'synthetic.staff', 'password_hash' => 'Synthetic current passphrase']);
        $password = match ($case) {
            'short' => str_repeat('s', 14), 'long' => str_repeat('l', 129), 'null' => "Synthetic\0new password",
            'reuse' => 'Synthetic current passphrase', default => 'synthetic.staff1234567890'
        };
        $before = $user->password_hash;

        $this->actingAs($user)->post(route('password.change.update'), ['current_password' => 'Synthetic current passphrase',
            'password' => $password, 'password_confirmation' => $password])->assertSessionHasErrors('password');

        $this->assertSame($before, $user->fresh()->password_hash);
        $this->assertSame(1, $user->fresh()->credential_version);
    }

    public function test_new_passwords_are_screened_against_the_pinned_local_list(): void
    {
        $common = collect(file(resource_path('security/common-passwords.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
            ->first(fn ($value) => mb_strlen($value) >= 15 && mb_strlen($value) <= 128);
        $this->assertNotNull($common, 'The denylist must contain entries within the accepted password length.');
        $user = User::factory()->create(['password_hash' => 'Synthetic current passphrase']);

        $this->actingAs($user)->post(route('password.change.update'), ['current_password' => 'Synthetic current passphrase',
            'password' => $common, 'password_confirmation' => $common])->assertSessionHasErrors('password');

        $this->assertSame(1, $user->fresh()->credential_version);
    }

    public function test_whitespace_and_long_unicode_passwords_are_preserved_and_the_current_session_survives(): void
    {
        $user = User::factory()->create(['password_hash' => '  Synthetic current passphrase  ']);
        $password = '  '.str_repeat('é', 124).'  ';
        $this->actingAs($user)->post(route('password.change.update'), ['current_password' => '  Synthetic current passphrase  ',
            'password' => $password, 'password_confirmation' => $password])->assertRedirectToRoute('dashboard');

        $this->assertTrue(Hash::check($password, $user->fresh()->password_hash));
        $this->assertFalse(Hash::check(trim($password), $user->fresh()->password_hash));
        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_administrator_reauthentication_preserves_password_whitespace(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create(['password_hash' => '  Synthetic administrator password  ']);
        $reset = app(PasswordRecovery::class)->request($user->username);

        $this->actingAs($admin)->patch(route('administration.password-reset-requests.update', $reset),
            ['decision' => 'approve', 'identity_verified' => true, 'admin_password' => '  Synthetic administrator password  '])->assertOk();

        $this->assertSame(PasswordResetStatus::Approved, $reset->fresh()->status);
        $this->assertArrayNotHasKey('recovery_code_hash', $reset->fresh()->toArray());
    }

    public function test_staff_cannot_list_review_approve_or_initiate_other_users_recovery(): void
    {
        $user = User::factory()->create();
        $reset = app(PasswordRecovery::class)->request($user->username);
        $this->actingAs(User::factory()->create())->get(route('administration.password-reset-requests.index'))->assertForbidden();
        $this->get(route('administration.password-reset-requests.show', $reset))->assertForbidden();
        $this->patch(route('administration.password-reset-requests.update', $reset), ['decision' => 'approve'])->assertForbidden();
        $this->post(route('administration.password-reset-requests.store', $user))->assertForbidden();

        $this->assertSame(PasswordResetStatus::Pending, $reset->fresh()->status);
    }

    public function test_rejection_is_terminal_and_does_not_change_the_password(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic administrator password']);
        $service = app(PasswordRecovery::class);
        $reset = $service->request($user->username);
        $before = $user->password_hash;

        $this->actingAs($admin)->patch(route('administration.password-reset-requests.update', $reset),
            ['decision' => 'reject', 'admin_password' => 'Synthetic administrator password'])->assertRedirectToRoute('administration.password-reset-requests.index');

        $this->assertSame(PasswordResetStatus::Rejected, $reset->fresh()->status);
        $this->assertSame($before, $user->fresh()->password_hash);
        $this->assertNotSame($reset->id, $service->request($user->username)->id);
    }

    public function test_deactivation_revokes_sessions_tokens_and_any_approved_code(): void
    {
        $user = User::factory()->create(['remember_token' => 'synthetic persistent token']);
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic administrator password']);
        $service = app(PasswordRecovery::class);
        $reset = $service->request($user->username);
        $code = $service->review($reset, $admin, 'approve', true, 'Synthetic administrator password');

        $this->actingAs($admin)->patch(route('administration.users.status.update', $user), ['is_active' => false])->assertRedirect();

        $this->assertFalse($user->fresh()->is_active);
        $this->assertSame(2, $user->fresh()->credential_version);
        $this->assertNull($user->fresh()->remember_token);
        $this->assertSame(PasswordResetStatus::Expired, $reset->fresh()->status);
        $this->assertFalse($service->complete($user->username, $code, 'Synthetic replacement passphrase'));
    }

    public function test_database_sessions_are_deleted_after_recovery(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic administrator password']);
        $service = app(PasswordRecovery::class);
        $reset = $service->request($user->username);
        $code = $service->review($reset, $admin, 'approve', true, 'Synthetic administrator password');
        DB::table('sessions')->insert(['id' => 'synthetic session', 'user_id' => $user->id, 'payload' => 'synthetic', 'last_activity' => time()]);
        config(['session.driver' => 'database']);

        $this->assertTrue($service->complete($user->username, $code, 'Synthetic replacement passphrase'));

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
    }

    public function test_voluntary_change_invalidates_previously_approved_recovery(): void
    {
        $user = User::factory()->create(['password_hash' => 'Synthetic current passphrase']);
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic administrator password']);
        $service = app(PasswordRecovery::class);
        $reset = $service->request($user->username);
        $code = $service->review($reset, $admin, 'approve', true, 'Synthetic administrator password');

        $this->actingAs($user)->post(route('password.change.update'), ['current_password' => 'Synthetic current passphrase',
            'password' => 'Synthetic replacement passphrase', 'password_confirmation' => 'Synthetic replacement passphrase'])->assertSessionHasNoErrors();

        $this->assertSame(PasswordResetStatus::Expired, $reset->fresh()->status);
        $this->assertFalse($service->complete($user->username, $code, 'Another synthetic replacement'));
    }

    public function test_approval_audit_failure_does_not_issue_a_code(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic administrator password']);
        $reset = app(PasswordRecovery::class)->request($user->username);
        Exceptions::fake();
        Event::listen('eloquent.creating: '.AuditLog::class, fn () => throw new RuntimeException('Synthetic audit failure'));

        $this->actingAs($admin)->patch(route('administration.password-reset-requests.update', $reset),
            ['decision' => 'approve', 'identity_verified' => true, 'admin_password' => 'Synthetic administrator password'])->assertInternalServerError();

        $this->assertSame(PasswordResetStatus::Pending, $reset->fresh()->status);
        $this->assertNull($reset->fresh()->recovery_code_hash);
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_revoked_administrator_credentials_cannot_approve_recovery_from_a_stale_session(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic administrator password']);
        $reset = app(PasswordRecovery::class)->request($user->username);
        DB::table('users')->where('id', $admin->id)->update(['credential_version' => 2]);

        $this->actingAs($admin)->patch(route('administration.password-reset-requests.update', $reset),
            ['decision' => 'approve', 'identity_verified' => true, 'admin_password' => 'Synthetic administrator password'])->assertForbidden();

        $this->assertSame(PasswordResetStatus::Pending, $reset->fresh()->status);
    }

    public function test_username_suggestion_reserves_both_name_components_and_suffix_space(): void
    {
        $service = app(Username::class);
        $first = str_repeat('a', 100);
        $last = str_repeat('b', 100);
        $suggestion = $service->suggestion($first, $last);
        User::factory()->create(['username' => $suggestion]);

        $this->assertSame(str_repeat('a', 20).'.'.str_repeat('b', 19), $suggestion);
        $this->assertSame(str_repeat('a', 20).'.'.str_repeat('b', 18).'2', $service->suggestion($first, $last));
    }

    public function test_sessions_without_a_credential_version_require_a_fresh_login(): void
    {
        $this->actingAs(User::factory()->create());
        session()->forget('credential_version');

        $this->get(route('dashboard'))->assertRedirectToRoute('login');

        $this->assertGuest();
    }

    #[TestWith(['must_change_password', true, 'password.change'])]
    #[TestWith(['credential_version', 2, 'login'])]
    public function test_real_livewire_requests_enforce_current_credentials_before_account_actions(string $field, mixed $value, string $destination): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();
        $page = $this->actingAs($admin)->get(route('administration.dashboard'))->assertOk();
        $snapshot = Utils::extractAttributeDataFromHtml($page->getContent(), 'wire:snapshot');
        DB::table('users')->where('id', $admin->id)->update([$field => $value]);
        $this->actingAs($admin->fresh())->withSession(['credential_version' => 1]);

        $this->postJson(Livewire::getUpdateUri(), ['components' => [['snapshot' => json_encode($snapshot), 'updates' => [],
            'calls' => [['path' => '', 'method' => 'toggleUserStatus', 'params' => [$staff->id]]]]]], ['X-Livewire' => 'true'])
            ->assertRedirectToRoute($destination);

        $this->assertTrue($staff->fresh()->is_active);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'user.access_changed']);
    }

    public function test_migration_stops_on_existing_case_collisions_without_changing_usernames(): void
    {
        $first = User::factory()->create(['username' => 'synthetic.first']);
        $second = User::factory()->create(['username' => 'synthetic.second']);
        $migration = require database_path('migrations/2026_10_26_000000_add_verified_password_recovery.php');
        $migration->down();
        DB::table('users')->where('id', $second->id)->update(['username' => 'SYNTHETIC.FIRST']);

        try {
            $migration->up();
            $this->fail('Case collisions must stop the migration.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Resolve case-insensitive username collisions', $exception->getMessage());
        }
        $this->assertFalse(Schema::hasColumn('users', 'credential_version'));
        $this->assertDatabaseHas('users', ['id' => $first->id, 'username' => 'synthetic.first']);
        $this->assertDatabaseHas('users', ['id' => $second->id, 'username' => 'SYNTHETIC.FIRST']);
    }
}
