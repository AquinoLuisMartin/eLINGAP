<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CheckDeployment extends Command
{
    protected $signature = 'app:check-deployment {--json : Print machine-readable results} {--database : Check connectivity and migration metadata}';

    protected $description = 'Check non-SMS deployment prerequisites without modifying configuration or records';

    public function handle(): int
    {
        $url = config('app.url', '');
        $key = config('app.key', '');
        $key = is_string($key) && str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
        $root = config('filesystems.disks.local.root');
        $private = is_string($root) && $root !== '' ? realpath($root) : false;
        $public = realpath(public_path());
        $mailer = config('mail.mailers.'.config('mail.default').'.transport');
        $queue = config('queue.connections.'.config('queue.default'), []);
        $checks = [
            'Production environment' => $this->laravel->isProduction(),
            'Debug disabled' => config('app.debug') === false,
            'PHP runtime' => PHP_VERSION_ID >= 80401,
            'Required PHP extensions' => collect(['ctype', 'curl', 'dom', 'fileinfo', 'filter', 'gd', 'hash', 'mbstring', 'openssl', 'pdo', 'pdo_pgsql', 'session', 'tokenizer', 'xml', 'zip'])->every(fn ($extension) => extension_loaded($extension)),
            'Valid encryption key' => is_string($key) && Encrypter::supported($key, config('app.cipher')),
            'HTTPS application URL' => filter_var($url, FILTER_VALIDATE_URL) !== false
                && strtolower(parse_url($url, PHP_URL_SCHEME) ?? '') === 'https'
                && ! parse_url($url, PHP_URL_USER) && ! parse_url($url, PHP_URL_PASS)
                && ! parse_url($url, PHP_URL_QUERY) && ! parse_url($url, PHP_URL_FRAGMENT),
            'Secure session cookies' => config('session.secure') === true && config('session.http_only') === true
                && in_array(config('session.same_site'), ['lax', 'strict'], true),
            'Encrypted session storage' => config('session.encrypt') === true,
            'Persistent sessions and cache' => in_array(config('session.driver'), ['file', 'cookie', 'database', 'memcached', 'redis', 'dynamodb'], true)
                && in_array(config('cache.stores.'.config('cache.default').'.driver'), ['database', 'file', 'memcached', 'redis', 'dynamodb', 'storage'], true),
            'Private local uploads' => config('filesystems.disks.local.driver') === 'local' && $private && $public
                && ! $this->inside($private, $public) && is_writable($private),
            'Writable runtime directories' => collect(['framework/cache/data', 'framework/sessions', 'framework/views', 'logs'])
                ->every(fn ($path) => is_dir(storage_path($path)) && is_writable(storage_path($path)))
                && is_writable(base_path('bootstrap/cache')),
            'Built frontend assets' => $this->assetsExist(),
            'Development asset server disabled' => ! is_file(public_path('hot')),
            'Environment file outside web root' => ! file_exists(public_path('.env')),
            'Argon2id password hashing' => in_array('argon2id', password_algos(), true) && config('hashing.driver') === 'argon2id'
                && config('hashing.argon.memory') >= 19456 && config('hashing.argon.time') >= 2 && config('hashing.argon.threads') >= 1,
            'Shared authentication rate limits' => in_array(config('cache.stores.'.config('cache.default').'.driver'), ['database', 'redis', 'memcached', 'dynamodb'], true),
            'Local password denylist' => is_readable(resource_path('security/common-passwords.txt')) && filesize(resource_path('security/common-passwords.txt')) > 0,
            'Persistent background queue' => ($queue['driver'] ?? null) === 'database'
                && ($queue['connection'] ?? config('database.default')) === config('database.default')
                && ($queue['table'] ?? null) === 'jobs' && ($queue['queue'] ?? null) === 'default',
            'Verified PostgreSQL TLS' => config('database.connections.'.config('database.default').'.sslmode') === 'verify-full',
        ];

        if ($this->option('database')) {
            try {
                DB::connection()->getPdo();
                $checks['Database connection'] = true;
                if (DB::connection()->getDriverName() === 'pgsql') {
                    $checks['Database TLS active'] = (bool) DB::scalar('SELECT ssl FROM pg_stat_ssl WHERE pid = pg_backend_pid()');
                    $checks['Restricted database role'] = ! (bool) DB::scalar("SELECT EXISTS (SELECT 1 FROM pg_roles WHERE (rolsuper OR rolcreatedb OR rolcreaterole OR rolbypassrls) AND pg_has_role(current_user, oid, 'MEMBER'))");
                }
                $migrator = $this->laravel->make('migrator');
                $repository = $migrator->getRepository();
                $checks['Migrations applied'] = $repository->repositoryExists()
                    && array_diff(array_keys($migrator->getMigrationFiles(database_path('migrations'))), $repository->getRan()) === [];
                foreach (['sessions' => config('session.driver') === 'database', 'cache' => config('cache.default') === 'database'] as $table => $required) {
                    if ($required) {
                        $connection = $table === 'sessions' ? config('session.connection') : config('cache.stores.database.connection');
                        $name = $table === 'sessions' ? config('session.table') : config('cache.stores.database.table');
                        $checks[ucfirst($table).' table'] = Schema::connection($connection)->hasTable($name);
                    }
                }
            } catch (Throwable) {
                $checks['Database checks completed'] = false;
            }
        }

        $ready = ! in_array(false, $checks, true);
        if ($this->option('json')) {
            $this->line(json_encode(['ready' => $ready, 'checks' => $checks], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } else {
            $this->table(['Check', 'Result'], collect($checks)->map(fn ($passed, $label) => [$label, $passed ? 'PASS' : 'FAIL'])->all());
            $this->line('This checks prerequisites only; verify TLS, backups, recovery, accessibility and performance on the target host.');
        }

        return $ready ? self::SUCCESS : self::FAILURE;
    }

    private function inside(string $path, string $parent): bool
    {
        $path = str_replace('\\', '/', $path);
        $parent = rtrim(str_replace('\\', '/', $parent), '/');
        if (PHP_OS_FAMILY === 'Windows') {
            $path = strtolower($path);
            $parent = strtolower($parent);
        }

        return $path === $parent || str_starts_with($path, $parent.'/');
    }

    private function assetsExist(): bool
    {
        $build = realpath(public_path('build'));
        $manifestPath = public_path('build/manifest.json');
        if (! $build || ! is_readable($manifestPath)) {
            return false;
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        foreach (['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'] as $entry) {
            $file = $manifest[$entry]['file'] ?? null;
            $path = is_string($file) ? realpath($build.'/'.$file) : false;
            if (! $path || ! is_file($path) || ! $this->inside($path, $build)) {
                return false;
            }
        }

        return true;
    }
}
