<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Auth\PasswordRecovery;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class RecoveryConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_simultaneous_requests_create_one_open_request(): void
    {
        $user = User::factory()->create();
        $results = $this->race(['action' => 'request', 'username' => $user->username]);

        $this->assertSame($results[0], $results[1]);
        $this->assertDatabaseCount('password_reset_requests', 1);
        $this->assertSame(1, AuditLog::where('action', 'user.recovery_requested')->count());
    }

    public function test_simultaneous_redemption_completes_and_updates_credentials_once(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic administrator password']);
        $service = app(PasswordRecovery::class);
        $reset = $service->request($user->username);
        $code = $service->review($reset, $admin, 'approve', true, 'Synthetic administrator password');
        $results = $this->race(['action' => 'complete', 'username' => $user->username, 'code' => $code, 'password' => 'Synthetic replacement passphrase']);

        sort($results);
        $this->assertSame([false, true], $results);
        $this->assertSame(2, $user->fresh()->credential_version);
        $this->assertTrue(Hash::check('Synthetic replacement passphrase', $user->fresh()->password_hash));
        $this->assertSame('COMPLETED', $reset->fresh()->status->value);
        $this->assertSame(1, AuditLog::where('action', 'user.recovery_completed')->count());
    }

    public function test_review_and_deactivation_share_lock_order_and_cannot_leave_a_live_code(): void
    {
        $admin = User::factory()->admin()->create(['password_hash' => 'Synthetic administrator password']);
        $user = User::factory()->create();
        $reset = app(PasswordRecovery::class)->request($user->username);
        $results = $this->race(['action' => 'review', 'admin' => $admin->id, 'user' => $user->id,
            'request' => $reset->id, 'password' => 'Synthetic administrator password'], ['action' => 'deactivate', 'admin' => $admin->id, 'user' => $user->id]);

        $this->assertSame('deactivated', $results[1]);
        $this->assertContains($results[0], ['approved', 'ineligible']);
        $this->assertFalse($user->fresh()->is_active);
        $this->assertSame('EXPIRED', $reset->fresh()->status->value);
        $this->assertNull($reset->fresh()->recovery_code_hash);
    }

    private function race(array $input, ?array $other = null): array
    {
        $this->assertSame('db_elingap_testing', DB::connection()->getDatabaseName());
        $barrier = storage_path('framework/testing/auth-barrier-'.bin2hex(random_bytes(8)));
        $processes = [];
        try {
            for ($index = 0; $index < 2; $index++) {
                $process = new Process([PHP_BINARY, base_path('tests/Fixtures/auth-recovery-worker.php')], base_path(),
                    ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_DATABASE' => 'db_elingap_testing', 'DB_URL' => '',
                        'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array'], json_encode([...($index === 1 && $other ? $other : $input), 'barrier' => $barrier, 'ready' => $barrier.'.'.$index.'.ready'])."\n", 15);
                $process->start();
                $processes[] = $process;
                file_put_contents($barrier.'.'.$index.'.pid', (string) $process->getPid());
            }
            $deadline = microtime(true) + 8;
            foreach ($processes as $index => $process) {
                while (! is_file($barrier.'.'.$index.'.ready') && microtime(true) < $deadline) {
                    $process->getOutput();
                    clearstatcache();
                    usleep(10000);
                }
                $this->assertFileExists($barrier.'.'.$index.'.ready', 'The recovery worker did not reach the barrier.');
            }
            touch($barrier);
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), 'The recovery worker failed.');
                $result = json_decode(explode("\n", trim($process->getOutput()))[1], true, flags: JSON_THROW_ON_ERROR);
                $this->assertArrayNotHasKey('error', $result);
                $results[] = $result['result'];
            }

            return $results;
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            if (is_file($barrier)) {
                unlink($barrier);
            }
            foreach (array_keys($processes) as $index) {
                if (is_file($barrier.'.'.$index.'.ready')) {
                    unlink($barrier.'.'.$index.'.ready');
                }
                if (is_file($barrier.'.'.$index.'.pid')) {
                    unlink($barrier.'.'.$index.'.pid');
                }
            }
        }
    }
}
