<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FreeTierReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_capacity_report_returns_database_sizes_without_connection_or_record_details(): void
    {
        Artisan::call('capacity:report', ['--json' => true]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(['database_bytes', 'budget_bytes', 'used_percent', 'warning', 'largest_tables'], array_keys($report));
        $this->assertSame(500000000, $report['budget_bytes']);
        $this->assertGreaterThan(0, $report['database_bytes']);
    }

    public function test_host_report_warns_on_a_stalled_queue_without_printing_job_payloads(): void
    {
        $this->freezeTime();
        config(['capacity.database_warning_percent' => 100000, 'capacity.disk_min_free_bytes' => 0]);
        DB::table('jobs')->insert(['queue' => 'default', 'payload' => 'synthetic-private-payload', 'attempts' => 0, 'reserved_at' => null, 'available_at' => now()->subMinutes(20)->timestamp, 'created_at' => now()->subMinutes(20)->timestamp]);

        $exit = Artisan::call('capacity:report', ['--json' => true, '--host' => true]);

        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(1, $exit);
        $this->assertTrue($report['warning']);
        $this->assertSame(['jobs' => 1, 'oldest_ready_seconds' => 1200], $report['queue']);
        $this->assertGreaterThan(0, $report['host']['disk_free_bytes']);
        $this->assertStringNotContainsString('synthetic-private-payload', Artisan::output());
    }

    public function test_future_delayed_jobs_do_not_raise_a_stalled_queue_warning(): void
    {
        $this->freezeTime();
        config(['capacity.database_warning_percent' => 100000, 'capacity.disk_min_free_bytes' => 0]);
        DB::table('jobs')->insert(['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => now()->addHour()->timestamp, 'created_at' => now()->subHour()->timestamp]);

        $exit = Artisan::call('capacity:report', ['--json' => true, '--host' => true]);

        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(0, $exit);
        $this->assertSame(0, $report['queue']['oldest_ready_seconds']);
        $this->assertFalse($report['warning']);
    }

    public function test_host_report_warns_when_free_disk_headroom_is_below_the_configured_reserve(): void
    {
        config(['capacity.disk_min_free_bytes' => PHP_INT_MAX]);

        $exit = Artisan::call('capacity:report', ['--json' => true, '--host' => true]);

        $this->assertSame(1, $exit);
        $this->assertTrue(json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR)['warning']);
    }
}
