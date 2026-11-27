<?php

namespace Tests\Feature;

use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\ReferenceData;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OfficeLocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReferenceDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_does_not_insert_reference_records(): void
    {
        $this->artisan('app:sync-reference-data', ['--source' => $this->source($this->data())])->assertSuccessful();

        foreach (array_keys($this->data()) as $table) {
            $this->assertDatabaseEmpty($table);
        }
    }

    public function test_import_adds_missing_data_and_preserves_existing_values_when_repeated(): void
    {
        Http::preventStrayRequests();
        Queue::fake();
        $source = $this->source($this->data());
        $this->artisan('app:sync-reference-data', ['--source' => $source, '--commit' => true])->assertSuccessful();
        DB::table('barangays')->update(['name' => 'Preserved barangay', 'is_active' => false]);
        DB::table('programs')->update(['name' => 'Preserved program', 'default_amount' => 999]);
        DB::table('sms_templates')->update(['body' => 'Preserved template']);
        DB::table('system_configurations')->where('key', 'municipality')->update(['value' => 'Preserved town']);
        $user = User::factory()->create();
        $account = $user->fresh()->getAttributes();

        $this->artisan('app:sync-reference-data', ['--source' => $source, '--commit' => true])->assertSuccessful();

        $this->assertDatabaseHas('barangays', ['name' => 'Preserved barangay', 'is_active' => false]);
        $this->assertDatabaseHas('programs', ['name' => 'Preserved program', 'default_amount' => 999]);
        $this->assertDatabaseHas('sms_templates', ['body' => 'Preserved template']);
        $this->assertDatabaseHas('system_configurations', ['key' => 'municipality', 'value' => 'Preserved town']);
        $this->assertDatabaseCount('barangays', 1);
        $this->assertDatabaseCount('programs', 1);
        $this->assertDatabaseCount('sms_templates', 1);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($account, $user->fresh()->getAttributes());
        $this->assertDatabaseEmpty('senior_citizens');
        $this->assertDatabaseEmpty('sms_messages');
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_export_contains_only_reference_fields_and_locality(): void
    {
        app(ReferenceData::class)->apply($this->data(), true);
        User::factory()->create();
        SystemConfiguration::forceCreate(['key' => 'support_phone', 'value' => 'Synthetic private setting']);
        Storage::fake('local');
        $path = Storage::disk('local')->path('reference.json');

        $this->artisan('app:sync-reference-data', ['--export' => $path])->assertSuccessful();

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(array_keys($this->data()), array_keys($data));
        foreach ($data as $rows) {
            foreach ($rows as $row) {
                $this->assertArrayNotHasKey('id', $row);
                $this->assertArrayNotHasKey('created_by', $row);
                $this->assertArrayNotHasKey('updated_by', $row);
            }
        }
        $this->assertSame(['municipality', 'province'], array_column($data['system_configurations'], 'key'));
        $this->assertStringNotContainsString('Synthetic private setting', file_get_contents($path));
    }

    public function test_production_import_requires_force_and_still_defaults_to_preview(): void
    {
        $this->app->instance('env', 'production');
        $source = $this->source($this->data());
        $this->artisan('app:sync-reference-data', ['--source' => $source])->assertSuccessful();
        $this->artisan('app:sync-reference-data', ['--source' => $source, '--commit' => true])->assertFailed();
        $this->assertDatabaseEmpty('barangays');
        $this->artisan('app:sync-reference-data', ['--source' => $source, '--commit' => true, '--force' => true])->assertSuccessful();
        $this->assertDatabaseCount('barangays', 1);
    }

    public function test_unapproved_columns_are_rejected_before_any_import(): void
    {
        $data = $this->data();
        $data['programs'][0]['created_by'] = 1;

        $this->artisan('app:sync-reference-data', ['--source' => $this->source($data), '--commit' => true])->assertFailed();

        $this->assertDatabaseEmpty('roles');
        $this->assertDatabaseEmpty('programs');
    }

    public function test_database_failure_rolls_back_the_entire_reference_import(): void
    {
        $data = $this->data();
        $data['programs'][0]['budget'] = 'invalid';

        $this->artisan('app:sync-reference-data', ['--source' => $this->source($data), '--commit' => true])->assertFailed();

        $this->assertDatabaseEmpty('roles');
        $this->assertDatabaseEmpty('barangays');
        $this->assertDatabaseEmpty('programs');
    }

    public function test_location_seeding_fills_missing_values_and_preserves_configured_values(): void
    {
        SystemConfiguration::forceCreate(['key' => 'province', 'value' => 'Preserved province']);
        SystemConfiguration::forceCreate(['key' => 'municipality', 'value' => '']);

        $this->seed(OfficeLocationSeeder::class);
        $this->seed(OfficeLocationSeeder::class);

        $this->assertDatabaseHas('system_configurations', ['key' => 'municipality', 'value' => 'Sta. Maria']);
        $this->assertDatabaseHas('system_configurations', ['key' => 'province', 'value' => 'Preserved province']);
        $this->assertDatabaseCount('system_configurations', 2);
    }

    public function test_default_seeding_includes_location_and_program_sms_templates(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('system_configurations', ['key' => 'municipality', 'value' => 'Sta. Maria']);
        $this->assertDatabaseHas('system_configurations', ['key' => 'province', 'value' => 'Bulacan']);
        $this->assertDatabaseHas('barangays', ['code' => 'SCL', 'name' => 'Sta. Clara']);
        $this->assertDatabaseHas('barangays', ['code' => 'SCR', 'name' => 'Sta. Cruz']);
        $this->assertDatabaseHas('programs', ['agency' => 'OSCA Sta. Maria']);
        $this->assertDatabaseCount('sms_templates', 9);
        $this->assertDatabaseEmpty('users');
    }

    public function test_place_name_migration_updates_existing_reference_labels_in_place(): void
    {
        $barangayId = DB::table('barangays')->insertGetId(['code' => 'SCL', 'name' => 'Santa Clara']);
        DB::table('programs')->insert(['name' => 'Synthetic program', 'agency' => 'OSCA Santa Maria']);
        SystemConfiguration::forceCreate(['key' => 'office_address', 'value' => 'Municipal Hall, SANTA MARIA']);
        SystemConfiguration::forceCreate(['key' => 'unrelated', 'value' => 'Santa Clara']);
        $migration = require database_path('migrations/2026_11_26_002711_abbreviate_santa_in_place_names.php');

        $migration->up();
        $migration->up();

        $this->assertDatabaseHas('barangays', ['id' => $barangayId, 'code' => 'SCL', 'name' => 'Sta. Clara']);
        $this->assertDatabaseHas('programs', ['name' => 'Synthetic program', 'agency' => 'OSCA Sta. Maria']);
        $this->assertDatabaseHas('system_configurations', ['key' => 'office_address', 'value' => 'Municipal Hall, Sta. MARIA']);
        $this->assertDatabaseHas('system_configurations', ['key' => 'unrelated', 'value' => 'Santa Clara']);
    }

    private function source(array $data): string
    {
        Storage::fake('local');
        $path = Storage::disk('local')->path('reference.json');
        file_put_contents($path, json_encode($data, JSON_THROW_ON_ERROR));

        return $path;
    }

    private function data(): array
    {
        return [
            'roles' => [['name' => 'ADMIN', 'description' => 'Administrator']],
            'barangays' => [['name' => 'Synthetic barangay', 'code' => 'SYN', 'is_active' => true]],
            'programs' => [['name' => 'Synthetic program', 'agency' => 'Synthetic office', 'benefit_code' => 'synthetic-reference', 'budget' => 0, 'status' => 'UPCOMING', 'benefit_rules' => '{"cadence":"ANNUAL"}']],
            'sms_templates' => [['name' => 'Synthetic template', 'body' => 'Synthetic requirements notice.', 'is_active' => true]],
            'system_configurations' => [['key' => 'municipality', 'value' => 'Sta. Maria', 'type' => 'string'], ['key' => 'province', 'value' => 'Bulacan', 'type' => 'string']],
        ];
    }
}
