<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FreeTierReport extends Command
{
    protected $signature = 'capacity:report {--json : Output aggregate sizes without record data} {--host : Include local disk space and queue backlog}';

    protected $description = 'Report PostgreSQL size against the Supabase free database allowance';

    public function handle(): int
    {
        $bytes = (int) DB::scalar('SELECT pg_database_size(current_database())');
        $tables = DB::select('SELECT relname AS table_name, pg_total_relation_size(relid) AS bytes, n_live_tup AS estimated_rows FROM pg_stat_user_tables WHERE schemaname = current_schema() ORDER BY pg_total_relation_size(relid) DESC LIMIT 10');
        $budget = (int) config('capacity.database_budget_bytes');
        $report = ['database_bytes' => $bytes, 'budget_bytes' => $budget, 'used_percent' => round($bytes / $budget * 100, 2), 'warning' => $bytes >= $budget * config('capacity.database_warning_percent') / 100, 'largest_tables' => $tables];
        if ($this->option('host')) {
            $free = disk_free_space(storage_path());
            $jobs = DB::table('jobs')->selectRaw('count(*) as total, min(case when available_at <= ? then created_at end) as oldest_ready', [now()->timestamp])->first();
            $queue = ['jobs' => (int) $jobs->total, 'oldest_ready_seconds' => $jobs->oldest_ready === null ? 0 : max(0, now()->timestamp - (int) $jobs->oldest_ready)];
            $report['host'] = ['disk_total_bytes' => disk_total_space(storage_path()), 'disk_free_bytes' => $free, 'disk_min_free_bytes' => config('capacity.disk_min_free_bytes')];
            $report['queue'] = $queue;
            $report['warning'] = $report['warning'] || $free === false || $free < config('capacity.disk_min_free_bytes')
                || $queue['jobs'] >= config('capacity.queue_warning_jobs') || $queue['oldest_ready_seconds'] >= config('capacity.queue_warning_age_seconds');
        }
        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_THROW_ON_ERROR));
        } else {
            $this->line(sprintf('Database: %.2f MB / 500 MB (%.2f%%)', $bytes / 1000000, $report['used_percent']));
            $this->table(['Table', 'MB including indexes', 'Estimated rows'], array_map(fn ($table) => [$table->table_name, round($table->bytes / 1000000, 2), $table->estimated_rows], $tables));
            if ($this->option('host')) {
                $this->line(sprintf('Local disk free: %.2f GiB; queued jobs: %d; oldest ready job: %d seconds', $report['host']['disk_free_bytes'] / 1073741824, $report['queue']['jobs'], $report['queue']['oldest_ready_seconds']));
            }
            if ($report['warning']) {
                $this->warn('A capacity threshold was reached. Review database growth, free disk space and queue backlog before increasing load.');
            }
        }

        return $report['warning'] ? self::FAILURE : self::SUCCESS;
    }
}
