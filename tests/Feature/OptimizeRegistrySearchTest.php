<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OptimizeRegistrySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_command_explains_search_without_creating_an_index_or_reading_records(): void
    {
        DB::connection()->enableQueryLog();
        DB::connection()->flushQueryLog();

        $this->artisan('app:optimize-registry-search')
            ->expectsOutputToContain('Compare plans, latency and index size')->assertSuccessful();

        $queries = DB::connection()->getQueryLog();
        DB::connection()->disableQueryLog();
        $this->assertCount(1, $queries);
        $this->assertStringStartsWith('EXPLAIN ', $queries[0]['query']);
    }
}
