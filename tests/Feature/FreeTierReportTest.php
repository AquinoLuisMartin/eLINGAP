<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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
}
