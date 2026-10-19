<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FreeTierReport extends Command
{
    protected $signature = 'capacity:report {--json : Output aggregate sizes without record data}';

    protected $description = 'Report PostgreSQL size against the Supabase free database allowance';

    public function handle(): int
    {
        $bytes = (int) DB::scalar('SELECT pg_database_size(current_database())');
        $tables = DB::select('SELECT relname AS table_name, pg_total_relation_size(relid) AS bytes, n_live_tup AS estimated_rows FROM pg_stat_user_tables WHERE schemaname = current_schema() ORDER BY pg_total_relation_size(relid) DESC LIMIT 10');
        $budget = 500000000;
        $report = ['database_bytes' => $bytes, 'budget_bytes' => $budget, 'used_percent' => round($bytes / $budget * 100, 2), 'warning' => $bytes >= $budget * 0.8, 'largest_tables' => $tables];
        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_THROW_ON_ERROR));
        } else {
            $this->line(sprintf('Database: %.2f MB / 500 MB (%.2f%%)', $bytes / 1000000, $report['used_percent']));
            $this->table(['Table', 'MB including indexes', 'Estimated rows'], array_map(fn ($table) => [$table->table_name, round($table->bytes / 1000000, 2), $table->estimated_rows], $tables));
            if ($report['warning']) {
                $this->warn('At least 80% of the free allowance is used. Review growth and arrange additional database capacity.');
            }
        }

        return $report['warning'] ? self::FAILURE : self::SUCCESS;
    }
}
