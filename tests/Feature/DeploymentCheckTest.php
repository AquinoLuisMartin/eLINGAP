<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DeploymentCheckTest extends TestCase
{
    #[TestWith(['app.key', null, 'Valid encryption key'])]
    #[TestWith(['filesystems.disks.local.root', '', 'Private local uploads'])]
    #[TestWith(['cache.default', 'missing-store', 'Persistent sessions and cache'])]
    public function test_missing_configuration_fails_safely(string $key, mixed $value, string $check): void
    {
        config([$key => $value]);

        $this->assertSame(1, Artisan::call('app:check-deployment', ['--json' => true]));

        $this->assertFalse(json_decode(Artisan::output(), true)['checks'][$check]);
    }

    public function test_check_reports_unsafe_settings_without_printing_secrets_or_querying_records(): void
    {
        config(['app.debug' => true, 'app.key' => 'synthetic-sensitive-value', 'app.url' => 'http://localhost', 'session.secure' => false, 'session.encrypt' => false]);
        DB::connection()->enableQueryLog();

        $exit = Artisan::call('app:check-deployment', ['--json' => true]);
        $output = Artisan::output();
        $result = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(1, $exit);
        $this->assertFalse($result['ready']);
        foreach (['Production environment', 'Debug disabled', 'Valid encryption key', 'HTTPS application URL', 'Secure session cookies', 'Encrypted session storage'] as $check) {
            $this->assertFalse($result['checks'][$check]);
        }
        $this->assertStringNotContainsString('synthetic-sensitive-value', $output);
        $this->assertSame([], DB::connection()->getQueryLog());
    }

    public function test_configured_prerequisites_pass_and_public_uploads_are_rejected(): void
    {
        Storage::fake('deployment');
        $disk = Storage::disk('deployment');
        $disk->makeDirectory('public/build/assets');
        $disk->makeDirectory('private');
        $this->app->usePublicPath($disk->path('public'));
        $manifest = [];
        foreach (['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'] as $index => $entry) {
            $disk->put('public/build/assets/'.$index, 'synthetic asset');
            $manifest[$entry] = ['file' => 'assets/'.$index];
        }
        $disk->put('public/build/manifest.json', json_encode($manifest));
        $this->app->instance('env', 'production');
        config([
            'app.debug' => false, 'app.key' => 'base64:'.base64_encode(str_repeat('s', 32)), 'app.cipher' => 'AES-256-CBC',
            'app.url' => 'https://elingap.example.test', 'session.secure' => true, 'session.http_only' => true,
            'session.same_site' => 'lax', 'session.encrypt' => true, 'session.driver' => 'database',
            'cache.default' => 'database', 'filesystems.disks.local.driver' => 'local',
            'filesystems.disks.local.root' => $disk->path('private'), 'mail.default' => 'smtp',
        ]);

        $this->assertSame(0, Artisan::call('app:check-deployment', ['--json' => true]), Artisan::output());

        config(['filesystems.disks.local.root' => public_path()]);
        $this->assertSame(1, Artisan::call('app:check-deployment', ['--json' => true]));
        $this->assertFalse(json_decode(Artisan::output(), true)['checks']['Private local uploads']);
    }

    public function test_database_failures_return_a_safe_failure_status(): void
    {
        config(['database.default' => 'synthetic-unconfigured-connection']);

        $this->assertSame(1, Artisan::call('app:check-deployment', ['--json' => true, '--database' => true]));
        $result = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertFalse($result['checks']['Database checks completed']);
        $this->assertStringNotContainsString('synthetic-unconfigured-connection', Artisan::output());
    }

    public function test_missing_build_files_cannot_pass_with_a_manifest_alone(): void
    {
        Storage::fake('deployment');
        $disk = Storage::disk('deployment');
        $disk->put('public/build/manifest.json', '{"resources/css/app.css":{"file":"missing.css"}}');
        $this->app->usePublicPath($disk->path('public'));

        Artisan::call('app:check-deployment', ['--json' => true]);

        $this->assertFalse(json_decode(Artisan::output(), true)['checks']['Built frontend assets']);
    }
}
