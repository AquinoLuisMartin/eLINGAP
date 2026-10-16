<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DeploymentDatabaseCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_check_reads_connectivity_and_migration_metadata(): void
    {
        config(['session.driver' => 'database', 'cache.default' => 'database']);

        Artisan::call('app:check-deployment', ['--json' => true, '--database' => true]);
        $result = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        foreach (['Database connection', 'Migrations applied', 'Sessions table', 'Cache table'] as $check) {
            $this->assertTrue($result['checks'][$check]);
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('senior_citizens', 0);
    }
}
