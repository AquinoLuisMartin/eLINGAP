<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneOperationalLogs extends Command
{
    protected $signature = 'app:prune-operational-logs {--dry-run : Preview eligible rows without deleting them}';

    protected $description = 'Prune bounded batches of expired operational logs under configured retention policies';

    public function handle(): int
    {
        foreach (['login_logs' => 'created_at', 'sms_delivery_logs' => 'recorded_at', 'failed_jobs' => 'failed_at'] as $table => $column) {
            $days = (int) config('retention.'.$table.'_days');
            if ($days <= 0) {
                $this->line($table.': retention disabled');

                continue;
            }
            $cutoff = now()->subDays($days);
            $total = 0;
            $lastId = 0;
            for ($batch = 0; $batch < 5; $batch++) {
                $ids = DB::table($table)->where($column, '<', $cutoff)->where('id', '>', $lastId)->orderBy('id')->limit(1000)->pluck('id');
                if ($ids->isEmpty()) {
                    break;
                }
                $lastId = $ids->last();
                $total += $this->option('dry-run') ? $ids->count() : DB::table($table)->whereIn('id', $ids)->where($column, '<', $cutoff)->delete();
            }
            $this->line($table.': '.$total.($this->option('dry-run') ? ' eligible' : ' pruned'));
        }

        return self::SUCCESS;
    }
}
