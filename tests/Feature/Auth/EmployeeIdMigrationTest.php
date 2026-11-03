<?php

namespace Tests\Feature\Auth;

use App\Models\LoginLog;
use App\Models\User;
use App\Services\Auth\PasswordRecovery;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class EmployeeIdMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_switch_preserves_accounts_passwords_and_historical_login_logs(): void
    {
        $user = User::factory()->create(['employee_id' => '2026-0001']);
        $attributes = $user->fresh()->getAttributes();
        LoginLog::create(['user_id' => $user->id, 'employee_id_attempted' => 'legacy.staff', 'event' => 'LOGIN', 'success' => true]);
        $migration = $this->migration();
        $migration->down();

        $migration->up();

        $this->assertSame($attributes, $user->fresh()->getAttributes());
        $this->assertDatabaseHas('login_logs', ['user_id' => $user->id, 'employee_id_attempted' => 'legacy.staff']);
        $this->assertFalse(Schema::hasColumn('users', 'username'));
        $this->assertFalse(Schema::hasColumn('login_logs', 'username_attempted'));
    }

    public function test_switch_refuses_unassigned_accounts_without_changing_them(): void
    {
        $user = User::factory()->create();
        $migration = $this->migration();
        $migration->down();
        DB::table('users')->where('id', $user->id)->update(['username' => 'legacy.staff']);

        try {
            $migration->up();
            $this->fail('Unassigned accounts must prevent the switch.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Assign official employee IDs to every account', $exception->getMessage());
        }

        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => 'legacy.staff']);
        $this->assertFalse(Schema::hasColumn('users', 'employee_id'));
        $this->assertTrue(Schema::hasColumn('login_logs', 'username_attempted'));
    }

    #[TestWith(['2026-001'])]
    #[TestWith(['20260001'])]
    #[TestWith(['synthetic.staff'])]
    #[TestWith(['2026-00010'])]
    public function test_database_rejects_malformed_employee_ids(string $employeeId): void
    {
        try {
            DB::transaction(fn () => User::factory()->create(['employee_id' => $employeeId]));
            $this->fail('Malformed employee IDs must not be saved.');
        } catch (QueryException $exception) {
            $this->assertSame('23514', (string) $exception->getCode());
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_assignment_preview_leaves_accounts_unchanged_and_apply_preserves_passwords_and_roles(): void
    {
        $this->mock(MaintenanceMode::class)->shouldReceive('active')->andReturn(true);
        $user = User::factory()->create(['remember_token' => 'synthetic-remember-token']);
        $admin = User::factory()->admin()->inactive()->create();
        $reset = app(PasswordRecovery::class)->request($user->employee_id);
        $attributes = $user->getAttributes();
        $this->migration()->down();
        DB::table('users')->where('id', $user->id)->update(['username' => 'legacy.staff']);
        DB::table('users')->where('id', $admin->id)->update(['username' => 'legacy.admin']);
        DB::table('sessions')->insert(['id' => 'synthetic-session', 'user_id' => $user->id, 'payload' => 'synthetic', 'last_activity' => time()]);
        $file = $this->csv("user_id,employee_id\n{$user->id},2026-0001\n{$admin->id},2026-0002\n");

        $this->artisan('app:assign-employee-ids', ['file' => $file])->assertSuccessful();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => 'legacy.staff', 'credential_version' => 1]);
        $this->assertDatabaseCount('audit_logs', 1);

        $this->artisan('app:assign-employee-ids', ['file' => $file, '--apply' => true])->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => '2026-0001', 'password_hash' => $attributes['password_hash'],
            'role_id' => $attributes['role_id'], 'credential_version' => 2, 'remember_token' => null]);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'username' => '2026-0002', 'is_active' => false]);
        $this->assertDatabaseHas('password_reset_requests', ['id' => $reset->id, 'status' => 'EXPIRED', 'recovery_code_hash' => null]);
        $this->assertDatabaseMissing('sessions', ['id' => 'synthetic-session']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.employee_id_assigned', 'auditable_id' => $user->id]);
        $this->migration()->up();
        $this->assertSame('2026-0001', $user->fresh()->employee_id);
    }

    #[TestWith(['missing'])]
    #[TestWith(['unknown'])]
    #[TestWith(['duplicate_user'])]
    #[TestWith(['duplicate_employee'])]
    #[TestWith(['malformed'])]
    #[TestWith(['header'])]
    public function test_invalid_assignment_files_do_not_change_any_accounts(string $case): void
    {
        $this->mock(MaintenanceMode::class)->shouldReceive('active')->andReturn(true);
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->migration()->down();
        $before = DB::table('users')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $rows = match ($case) {
            'missing' => "user_id,employee_id\n{$user->id},2026-0001\n",
            'unknown' => "user_id,employee_id\n999999,2026-0001\n{$other->id},2026-0002\n",
            'duplicate_user' => "user_id,employee_id\n{$user->id},2026-0001\n{$user->id},2026-0002\n",
            'duplicate_employee' => "user_id,employee_id\n{$user->id},2026-0001\n{$other->id},2026-0001\n",
            'malformed' => "user_id,employee_id\n{$user->id},2026-001\n{$other->id},2026-0002\n",
            'header' => "id,employee_id\n{$user->id},2026-0001\n{$other->id},2026-0002\n",
        };

        $this->artisan('app:assign-employee-ids', ['file' => $this->csv($rows), '--apply' => true])->assertFailed();

        $this->assertSame($before, DB::table('users')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_assignment_can_swap_existing_identifiers_and_is_safe_to_repeat_before_migration(): void
    {
        $this->mock(MaintenanceMode::class)->shouldReceive('active')->andReturn(true);
        $first = User::factory()->create(['employee_id' => '2026-0001']);
        $second = User::factory()->create(['employee_id' => '2026-0002']);
        $this->migration()->down();
        $file = $this->csv("user_id,employee_id\n{$first->id},2026-0002\n{$second->id},2026-0001\n");

        $this->artisan('app:assign-employee-ids', ['file' => $file, '--apply' => true])->assertSuccessful();
        $this->artisan('app:assign-employee-ids', ['file' => $file, '--apply' => true])->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $first->id, 'username' => '2026-0002', 'credential_version' => 2]);
        $this->assertDatabaseHas('users', ['id' => $second->id, 'username' => '2026-0001', 'credential_version' => 2]);
        $this->assertDatabaseCount('audit_logs', 2);
    }

    public function test_assignment_command_refuses_to_run_after_the_switch(): void
    {
        $this->artisan('app:assign-employee-ids', ['file' => 'unused.csv'])->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_assignment_requires_maintenance_mode_before_changing_accounts(): void
    {
        $this->mock(MaintenanceMode::class)->shouldReceive('active')->andReturn(false);
        $user = User::factory()->create();
        $this->migration()->down();
        $before = DB::table('users')->where('id', $user->id)->first()->username;

        $this->artisan('app:assign-employee-ids', ['file' => $this->csv("user_id,employee_id\n{$user->id},2026-0001\n"), '--apply' => true])
            ->expectsOutput('Put the application in maintenance mode before applying employee IDs.')->assertFailed();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => $before, 'credential_version' => 1]);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_assignment_rolls_back_all_changes_when_audit_storage_is_unavailable(): void
    {
        $this->mock(MaintenanceMode::class)->shouldReceive('active')->andReturn(true);
        $user = User::factory()->create();
        $this->migration()->down();
        $before = (array) DB::table('users')->where('id', $user->id)->first();
        DB::statement('ALTER TABLE audit_logs RENAME TO unavailable_audit_logs');

        $this->artisan('app:assign-employee-ids', ['file' => $this->csv("user_id,employee_id\n{$user->id},2026-0001\n"), '--apply' => true])
            ->assertFailed();

        $this->assertSame($before, (array) DB::table('users')->where('id', $user->id)->first());
        $this->assertDatabaseCount('unavailable_audit_logs', 0);
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_29_233527_replace_usernames_with_employee_ids.php');
    }

    private function csv(string $contents): string
    {
        Storage::fake('local');
        Storage::disk('local')->put('employee-ids.csv', $contents);

        return Storage::disk('local')->path('employee-ids.csv');
    }
}
