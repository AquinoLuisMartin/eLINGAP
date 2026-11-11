<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\Program;
use App\Models\SeniorCitizen;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SeedSystemTesting extends Command
{
    protected $signature = 'app:seed-system-testing
        {--commit : Insert reference and synthetic testing data}
        {--reference-only : Insert roles, barangays and benefit definitions only}
        {--force : Allow execution against a production-configured testing deployment}';

    protected $description = 'Add supported roles and a small synthetic system-testing dataset without replacing existing records';

    public function handle(): int
    {
        if (! $this->option('commit')) {
            $this->info('Preview: seeds Administrator and OSCA Staff roles, 24 barangays and nine benefit definitions.');
            if (! $this->option('reference-only')) {
                $this->info('Also adds 24 mock seniors, one mock program, 12 beneficiaries and six pending applications.');
            }
            $this->info('No changes made. Add --commit to apply. No accounts are created or SMS sent.');

            return self::SUCCESS;
        }

        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Use --force only after confirming this is the intended testing deployment.');

            return self::FAILURE;
        }

        DB::transaction(function (): void {
            if ($this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]) !== self::SUCCESS) {
                throw new RuntimeException('Reference seeding failed; testing data was not applied.');
            }
            if ($this->option('reference-only')) {
                return;
            }

            $barangays = Barangay::query()->where('is_active', true)->orderBy('id')->get();
            $program = Program::firstOrCreate(['name' => '[MOCK] System Testing Assistance'], [
                'agency' => 'OSCA', 'status' => 'ACTIVE',
                'description' => 'Synthetic system-testing program. No actual assistance or payments.',
            ]);

            for ($index = 1; $index <= 24; $index++) {
                $senior = SeniorCitizen::firstOrCreate([
                    'first_name' => 'MOCK', 'last_name' => sprintf('TEST SENIOR %02d', $index),
                    'address' => 'SYNTHETIC ADDRESS - NOT A RESIDENCE',
                ], [
                    'barangay_id' => $barangays[($index - 1) % $barangays->count()]->id,
                    'birth_date' => today()->subYears(60 + (($index - 1) % 9) * 5),
                    'sex' => $index % 2 === 0 ? 'MALE' : 'FEMALE',
                    'status' => $index <= 18 ? 'VERIFIED' : 'PENDING',
                    'verified_at' => $index <= 18 ? now() : null,
                ]);

                if ($index <= 12) {
                    Beneficiary::firstOrCreate([
                        'program_id' => $program->id, 'senior_citizen_id' => $senior->id,
                    ], ['status' => 'ACTIVE', 'enrolled_on' => today()]);
                } elseif ($index <= 18) {
                    Application::firstOrCreate([
                        'application_number' => sprintf('MOCK-SYSTEM-%02d', $index),
                    ], [
                        'program_id' => $program->id, 'senior_citizen_id' => $senior->id,
                        'applied_on' => today(), 'status' => 'PENDING',
                        'remarks' => '[MOCK] Synthetic application for system testing.',
                    ]);
                }
            }
        });

        $this->info('Testing data is ready. Existing records are preserved. No accounts created or SMS sent.');

        return self::SUCCESS;
    }
}
