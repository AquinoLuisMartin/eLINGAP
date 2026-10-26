<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\TestWith;
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

    #[TestWith(['2026_10_19_000000_add_targeted_capacity_indexes'])]
    #[TestWith(['2026_10_20_001228_add_benefit_preparation_and_integrity_constraints'])]
    public function test_database_check_rejects_pending_workflow_migrations(string $migration): void
    {
        DB::table('migrations')->where('migration', $migration)->delete();

        $exit = Artisan::call('app:check-deployment', ['--json' => true, '--database' => true]);
        $result = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(1, $exit);
        $this->assertFalse($result['checks']['Migrations applied']);
        $this->assertFalse($result['ready']);
        $this->assertDatabaseCount('senior_citizens', 0);
    }
}
