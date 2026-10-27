<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class SeedSampleRegistry extends Command
{
    protected $signature = 'app:seed-sample-registry {--commit : Back up, restore, rehearse and commit the sample dataset} {--rename : Replace only the existing sample name fields with fictional Filipino names} {--surname-only : Preserve first and middle names when renaming} {--pg-bin= : PostgreSQL binary directory}';

    protected $description = 'Append 10,000 synthetic seniors and related benefit records with transactional acceptance checks';

    public function handle(): int
    {
        if (! $this->option('commit')) {
            $this->info('No changes made. Use --commit to back up, restore, rehearse and seed the database.');

            return self::SUCCESS;
        }

        try {
            if ($this->option('surname-only') && ! $this->option('rename')) {
                throw new RuntimeException('Use --surname-only with --rename.');
            }
            $connection = DB::connection();
            if (app()->isProduction() || $connection->getDriverName() !== 'pgsql' || $connection->getConfig('search_path') !== 'public') {
                throw new RuntimeException('Use a non-production PostgreSQL database with the public search path.');
            }
            if (! $this->option('rename') && (DB::table('programs')->where('benefit_code', 'synthetic-registry-2000-2026')->exists()
                || DB::table('senior_citizens')->where('address', 'SYNTHETIC ADDRESS - NOT A RESIDENCE')->exists())) {
                throw new RuntimeException('Sample dataset or synthetic profiles already exist.');
            }

            $config = $connection->getConfig();
            $directory = storage_path('app/private/database-backups/'.date('Ymd-His').'-'.bin2hex(random_bytes(4)));
            if (! mkdir($directory, 0700, true)) {
                throw new RuntimeException('Cannot create the private backup directory.');
            }
            if (PHP_OS_FAMILY === 'Windows') {
                $identity = getenv('USERDOMAIN').'\\'.getenv('USERNAME');
                $permissions = new Process(['icacls', $directory, '/inheritance:r', '/grant:r', $identity.':(OI)(CI)F', 'SYSTEM:(OI)(CI)F']);
                $permissions->run();
                if (! $permissions->isSuccessful()) {
                    throw new RuntimeException('Cannot restrict backup directory permissions.');
                }
            }
            $backup = $directory.'/before-sample.dump';
            $environment = [
                'PGHOST' => $config['host'], 'PGPORT' => (string) $config['port'],
                'PGUSER' => $config['username'], 'PGPASSWORD' => $config['password'],
                'PGSSLMODE' => $config['sslmode'], 'PGCONNECT_TIMEOUT' => '10',
            ];
            if ($config['sslrootcert'] ?? null) {
                $environment['PGSSLROOTCERT'] = $config['sslrootcert'];
            }
            $run = function (string $binary, array $arguments) use ($environment): void {
                $path = $this->option('pg-bin') ? rtrim($this->option('pg-bin'), '/\\').DIRECTORY_SEPARATOR.$binary : $binary;
                $process = new Process([$path, ...$arguments], base_path(), $environment);
                $process->setTimeout(300);
                $process->run();
                if (! $process->isSuccessful()) {
                    $output = strtolower($process->getErrorOutput());
                    $reason = match (true) {
                        str_contains($output, 'version mismatch') => 'PostgreSQL client/server version mismatch',
                        str_contains($output, 'permission denied') => 'permission denied',
                        str_contains($output, 'password authentication failed') => 'database authentication failed',
                        str_contains($output, 'could not translate host name') => 'database host resolution failed',
                        str_contains($output, 'connection timed out'), str_contains($output, 'timeout expired') => 'database connection timed out',
                        str_contains($output, 'connection refused') => 'database connection refused',
                        str_contains($output, 'not recognized'), str_contains($output, 'cannot find') => 'PostgreSQL executable unavailable',
                        str_contains($output, 'does not exist') => 'required database object unavailable',
                        default => 'process exit code '.$process->getExitCode(),
                    };
                    throw new RuntimeException($binary.' failed: '.$reason.'. Seeding stopped; connection details withheld.');
                }
            };

            $this->info('Creating private backup and verifying a full restore.');
            $run('pg_dump', ['--no-password', '--format=custom', '--file='.$backup, '--dbname='.$config['database']]);
            chmod($backup, 0600);
            file_put_contents($backup.'.sha256', hash_file('sha256', $backup).PHP_EOL);
            $clone = 'elingap_sample_check_'.date('YmdHis').'_'.bin2hex(random_bytes(3));
            $run('createdb', ['--no-password', '--template=template0', $clone]);
            config(['database.connections.sample_verification' => [...$config, 'url' => null, 'database' => $clone]]);
            $verification = DB::connection('sample_verification');
            $verification->statement('REVOKE ALL ON DATABASE "'.$clone.'" FROM PUBLIC');
            $run('pg_restore', ['--no-password', '--no-owner', '--no-privileges', '--exit-on-error', '--single-transaction', '--dbname='.$clone, $backup]);

            $sql = "SELECT set_config('elingap.surname_only', '".($this->option('surname-only') ? 'true' : 'false')."', true);".PHP_EOL
                .file_get_contents(database_path('seeders/sql/sample-filipino-names.sql')).PHP_EOL
                .file_get_contents(database_path('seeders/sql/'.($this->option('rename') ? 'rename-sample-registry.sql' : 'sample-registry.sql')));
            $verification->transaction(fn () => $verification->unprepared($sql));
            $this->info('Backup restored; the sample operation and all acceptance checks passed on the restored copy.');

            $connection->transaction(fn () => $connection->unprepared($sql));
            $this->info($this->option('rename')
                ? ($this->option('surname-only')
                    ? 'Updated only surnames for 10,000 synthetic seniors; all other fields and datasets preserved.'
                    : 'Updated only first, middle and last names for 10,000 synthetic seniors; all other fields and datasets preserved.')
                : 'Committed 10,000 synthetic seniors and 10,000 rows in each of the five related datasets.');
            $this->line('Backup: '.$backup);
            $this->line('Restored rehearsal database retained: '.$clone);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception instanceof RuntimeException && ! $exception instanceof \PDOException
                && ! $exception instanceof QueryException
                ? $exception->getMessage()
                : 'Database operation failed; uncommitted sample inserts were rolled back. Connection details withheld.');
            if (isset($backup) && is_file($backup)) {
                $this->line('Backup retained: '.$backup);
            }
            if (isset($clone)) {
                $this->line('Verification database name: '.$clone);
            }

            return self::FAILURE;
        }
    }
}
