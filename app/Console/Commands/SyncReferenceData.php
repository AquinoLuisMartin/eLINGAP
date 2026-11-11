<?php

namespace App\Console\Commands;

use App\Services\ReferenceData;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Throwable;

class SyncReferenceData extends Command
{
    protected $signature = 'app:sync-reference-data
        {--export= : Export only approved local reference fields to this JSON path}
        {--source=database/seeders/data/local-reference-data.json : Reference snapshot to import}
        {--commit : Add missing reference records; existing records are preserved}
        {--force : Allow import into a production environment}';

    protected $description = 'Preview or add missing reference data without transferring accounts, personal records or credentials';

    public function handle(ReferenceData $references, Filesystem $files): int
    {
        if ($destination = $this->option('export')) {
            if ($this->option('commit')) {
                $this->error('Export and import must run separately.');

                return self::FAILURE;
            }
            $files->ensureDirectoryExists(dirname($destination));
            $files->replace($destination, json_encode($references->snapshot(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
            $this->info('Exported reference fields only; no user IDs, accounts, personal records, logs or credentials.');

            return self::SUCCESS;
        }

        if ($this->option('commit') && app()->isProduction() && ! $this->option('force')) {
            $this->error('Use --force to apply this reviewed reference snapshot in production.');

            return self::FAILURE;
        }

        try {
            $data = json_decode($files->get($this->option('source')), true, 512, JSON_THROW_ON_ERROR);
            $summary = $references->apply($data, (bool) $this->option('commit'));
        } catch (Throwable) {
            $this->error('Reference import failed. Verify the snapshot and database connection; no reference changes were committed.');

            return self::FAILURE;
        }
        $this->table(['Reference table', 'Missing', 'Existing (preserved)'], collect($summary)->map(fn ($counts, $table) => [$table, $counts['missing'], $counts['existing']])->all());
        $this->info($this->option('commit') ? 'Missing reference entries added. Existing values were preserved.' : 'Preview only. Add --commit to insert missing entries.');

        return self::SUCCESS;
    }
}
