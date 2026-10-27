<?php

namespace App\Console\Commands;

use App\Models\SeniorCitizen;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OptimizeRegistrySearch extends Command
{
    protected $signature = 'app:optimize-registry-search {--apply : Build a concurrent trigram index after measuring storage and query plans}';

    protected $description = 'Inspect the registry search plan, with optional PostgreSQL trigram indexing';

    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->error('This operation requires PostgreSQL.');

            return self::FAILURE;
        }
        if ($this->option('apply')) {
            $schema = DB::scalar("SELECT n.nspname FROM pg_extension e JOIN pg_namespace n ON n.oid = e.extnamespace WHERE e.extname = 'pg_trgm'");
            if (! $schema) {
                $this->error('Enable pg_trgm with the database administrator before building this index.');

                return self::FAILURE;
            }
            $operator = DB::connection()->getQueryGrammar()->wrap($schema.'.gin_trgm_ops');
            $columns = collect(['first_name', 'middle_name', 'last_name', 'osca_id_number'])
                ->map(fn ($column) => 'lower('.$column.') '.$operator)->implode(', ');
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_seniors_search_trgm ON senior_citizens USING gin ('.$columns.')');
            $valid = DB::scalar("SELECT i.indisvalid FROM pg_index i JOIN pg_class c ON c.oid = i.indexrelid WHERE c.relname = 'idx_seniors_search_trgm' AND i.indrelid = 'senior_citizens'::regclass");
            if (! $valid) {
                $this->error('The index is invalid. Have the database administrator remove it concurrently before retrying.');

                return self::FAILURE;
            }
        }
        $query = SeniorCitizen::query()->select('id')->searchable('SYNTHETIC')->orderBy('id')->limit(15);
        foreach (DB::select('EXPLAIN '.$query->toSql(), $query->getBindings()) as $row) {
            $this->line($row->{'QUERY PLAN'});
        }
        $this->info('Compare plans, latency and index size using synthetic staging data before applying to production.');

        return self::SUCCESS;
    }
}
