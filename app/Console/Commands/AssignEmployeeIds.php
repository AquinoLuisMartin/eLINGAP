<?php

namespace App\Console\Commands;

use App\Services\Auth\EmployeeId;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AssignEmployeeIds extends Command
{
    protected $signature = 'app:assign-employee-ids {file : Local CSV with user_id,employee_id headers} {--apply : Save the validated assignments before migration}';

    protected $description = 'Validate or assign official employee IDs before switching system access';

    public function handle(): int
    {
        if (! Schema::hasColumn('users', 'username')) {
            $this->error('Employee ID access is already enabled. Manage employee IDs through account administration.');

            return self::FAILURE;
        }
        if ($this->option('apply') && ! app()->isDownForMaintenance()) {
            $this->error('Put the application in maintenance mode before applying employee IDs.');

            return self::FAILURE;
        }

        try {
            $assignments = $this->assignments();
            $count = DB::transaction(function () use ($assignments): int {
                DB::statement('LOCK TABLE users IN ACCESS EXCLUSIVE MODE');
                $users = DB::table('users')->orderBy('id')->get(['id', 'username']);
                if (array_diff($users->pluck('id')->all(), array_keys($assignments)) || array_diff(array_keys($assignments), $users->pluck('id')->all())) {
                    throw new RuntimeException('The CSV must contain exactly one assignment for every existing account, including administrators and inactive accounts.');
                }
                $changed = $users->filter(fn ($user) => $user->username !== $assignments[$user->id])->pluck('id')->all();
                if (! $this->option('apply')) {
                    return count($changed);
                }

                foreach ($changed as $id) {
                    DB::table('users')->where('id', $id)->update(['username' => '__employee_id_migration_'.$id]);
                }
                foreach ($changed as $id) {
                    DB::table('users')->where('id', $id)->update([
                        'username' => $assignments[$id], 'credential_version' => DB::raw('credential_version + 1'),
                        'remember_token' => null, 'updated_at' => now(),
                    ]);
                    DB::table('audit_logs')->insert([
                        'action' => 'user.employee_id_assigned', 'auditable_type' => 'App\\Models\\User',
                        'auditable_id' => $id, 'new_values' => json_encode(['source' => 'console']), 'created_at' => now(),
                    ]);
                }
                DB::table('password_reset_requests')->whereIn('user_id', $changed)->whereIn('status', ['PENDING', 'APPROVED'])
                    ->update(['status' => 'EXPIRED', 'recovery_code_hash' => null, 'updated_at' => now()]);
                if (Schema::hasTable('sessions')) {
                    DB::table('sessions')->whereIn('user_id', $changed)->delete();
                }

                return count($changed);
            });
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->info($this->option('apply') ? "Assigned employee IDs to {$count} accounts. Run the database migration before reopening access." : "Validated assignments: {$count} accounts would change. Use --apply in maintenance mode to save them.");

        return self::SUCCESS;
    }

    private function assignments(): array
    {
        $path = $this->argument('file');
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('The assignment CSV must be a readable local file.');
        }
        $file = fopen($path, 'r');
        try {
            if (fgetcsv($file, escape: '') !== ['user_id', 'employee_id']) {
                throw new RuntimeException('The CSV header must be user_id,employee_id.');
            }
            $assignments = [];
            $employeeIds = [];
            while (($row = fgetcsv($file, escape: '')) !== false) {
                if (count($row) !== 2 || ! ctype_digit($row[0]) || (int) $row[0] < 1 || ! preg_match(EmployeeId::PATTERN, $row[1])) {
                    throw new RuntimeException('Each CSV row must contain a positive user ID and an official employee ID in YYYY-NNNN format.');
                }
                $id = (int) $row[0];
                if (isset($assignments[$id]) || isset($employeeIds[$row[1]])) {
                    throw new RuntimeException('The CSV contains duplicate user IDs or employee IDs.');
                }
                $assignments[$id] = $row[1];
                $employeeIds[$row[1]] = true;
            }

            return $assignments;
        } finally {
            fclose($file);
        }
    }
}
