<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BarangaySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SampleRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_dataset_meets_acceptance_checks_and_preserves_existing_records(): void
    {
        $this->prerequisites();
        $before = DB::table('barangays')->orderBy('id')->get()->toJson();
        $reserved = DB::table('osca_id_numbers')->insertGetId(['issued_year' => 2000, 'created_at' => '2000-01-01']);
        $existingId = DB::table('senior_citizens')->insertGetId([
            'barangay_id' => DB::table('barangays')->where('code', 'BAG')->value('id'),
            'registration_number' => 'KEEP-1', 'first_name' => 'SYNTHETIC', 'last_name' => 'Existing',
            'birth_date' => '1930-01-01', 'sex' => 'FEMALE', 'address' => 'SYNTHETIC',
            'osca_id_number' => sprintf('00-%04d', $reserved + 1),
        ]);
        $existing = DB::table('senior_citizens')->find($existingId);

        DB::transaction(fn () => DB::unprepared($this->sql()));

        $this->assertDatabaseCount('senior_citizens', 10001);
        foreach (['applications', 'beneficiaries', 'benefit_entitlements', 'benefit_payments', 'benefit_schedule_recipients'] as $table) {
            $this->assertDatabaseCount($table, 10000);
        }
        $this->assertSame($before, DB::table('barangays')->orderBy('id')->get()->toJson());
        $this->assertDatabaseHas('senior_citizens', ['registration_number' => 'SYN-00001', 'birth_date' => '1939-01-01']);
        $this->assertDatabaseHas('senior_citizens', ['registration_number' => 'SYN-10000']);
        $this->assertSame(371, DB::table('senior_citizens')->whereYear('created_at', 2000)->count());
        $this->assertSame(370, DB::table('senior_citizens')->whereYear('created_at', 2026)->count());
        $this->assertSame(10001, DB::table('senior_citizens')->distinct()->count('osca_id_number'));
        $this->assertEquals($existing, DB::table('senior_citizens')->find($existingId));
        $this->assertDatabaseHas('senior_citizens', ['registration_number' => 'SYN-00001', 'first_name' => 'Maria Josefina', 'middle_name' => 'Santiago', 'last_name' => 'Santos']);

        DB::statement('DROP TABLE sample_filipino_names');
        DB::table('senior_citizens')->where('registration_number', 'like', 'SYN-%')->update(['first_name' => 'SYNTHETIC', 'middle_name' => null, 'last_name' => 'Sample']);
        DB::transaction(fn () => DB::unprepared(file_get_contents(database_path('seeders/sql/sample-filipino-names.sql')).PHP_EOL
            .file_get_contents(database_path('seeders/sql/rename-sample-registry.sql'))));

        $this->assertDatabaseHas('senior_citizens', ['registration_number' => 'SYN-00001', 'first_name' => 'Maria Josefina', 'middle_name' => 'Santiago', 'last_name' => 'Santos']);
        $this->assertSame(10000, DB::table('senior_citizens')->where('registration_number', 'like', 'SYN-%')->whereNotNull('middle_name')->count());
        $this->assertSame(10000, DB::table('senior_citizens')->where('registration_number', 'like', 'SYN-%')->distinct()->count('first_name'));
        $this->assertSame(100, DB::table('senior_citizens')->where('registration_number', 'like', 'SYN-%')->distinct()->count('last_name'));
        $this->assertSame(0, DB::table('senior_citizens')->where('registration_number', 'like', 'SYN-%')->where('last_name', 'like', '%-%')->count());
        $this->assertSame(100, (int) DB::scalar("SELECT count(*) FROM (SELECT last_name FROM senior_citizens WHERE registration_number LIKE 'SYN-%' GROUP BY last_name HAVING count(DISTINCT first_name) = count(*) AND count(DISTINCT barangay_id) > 1) surnames"));
        $this->assertEquals($existing, DB::table('senior_citizens')->find($existingId));
    }

    public function test_missing_barangay_aborts_without_sample_inserts(): void
    {
        $this->prerequisites();
        DB::table('barangays')->where('code', 'BAG')->update(['is_active' => false]);

        try {
            DB::transaction(fn () => DB::unprepared($this->sql()));
            $this->fail('Missing barangay must reject the sample dataset.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('24 expected active barangays', $exception->getMessage());
        }

        $this->assertDatabaseCount('senior_citizens', 0);
        $this->assertDatabaseCount('programs', 0);
    }

    public function test_existing_record_changes_roll_back_all_sample_inserts(): void
    {
        $this->prerequisites();
        DB::unprepared("CREATE FUNCTION sample_test_change() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN UPDATE barangays SET is_active = false WHERE code = 'BAG'; RETURN NEW; END $$;
            CREATE TRIGGER sample_test_change BEFORE INSERT ON programs FOR EACH ROW EXECUTE FUNCTION sample_test_change()");

        try {
            DB::transaction(fn () => DB::unprepared($this->sql()));
            $this->fail('Changing an existing record must roll back the sample dataset.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Existing records changed', $exception->getMessage());
        }

        $this->assertDatabaseCount('senior_citizens', 0);
        $this->assertDatabaseCount('benefit_payments', 0);
        $this->assertDatabaseCount('programs', 0);
        $this->assertDatabaseHas('barangays', ['code' => 'BAG', 'is_active' => true]);
    }

    public function test_command_without_commit_does_not_write(): void
    {
        $this->artisan('app:seed-sample-registry')->assertSuccessful();

        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_surname_only_update_preserves_first_and_middle_names(): void
    {
        $this->prerequisites();
        DB::transaction(fn () => DB::unprepared($this->sql()));
        DB::statement('DROP TABLE sample_filipino_names');
        DB::table('senior_citizens')->where('registration_number', 'SYN-00001')->update([
            'first_name' => 'Maria Test', 'middle_name' => 'Retained', 'last_name' => 'Santos-Reyes',
        ]);

        DB::transaction(fn () => DB::unprepared("SELECT set_config('elingap.surname_only', 'true', true);".PHP_EOL
            .file_get_contents(database_path('seeders/sql/sample-filipino-names.sql')).PHP_EOL
            .file_get_contents(database_path('seeders/sql/rename-sample-registry.sql'))));

        $this->assertDatabaseHas('senior_citizens', ['registration_number' => 'SYN-00001', 'first_name' => 'Maria Test', 'middle_name' => 'Retained', 'last_name' => 'Santos']);
        $this->assertDatabaseCount('senior_citizens', 10000);
    }

    public function test_rename_rolls_back_when_a_trigger_changes_another_field(): void
    {
        $this->prerequisites();
        DB::transaction(fn () => DB::unprepared($this->sql()));
        DB::statement('DROP TABLE sample_filipino_names');
        DB::table('senior_citizens')->where('registration_number', 'SYN-00001')->update(['middle_name' => null]);
        DB::unprepared("CREATE FUNCTION sample_test_rename() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN NEW.address := 'Unexpected change'; RETURN NEW; END $$;
            CREATE TRIGGER sample_test_rename BEFORE UPDATE ON senior_citizens FOR EACH ROW EXECUTE FUNCTION sample_test_rename()");

        try {
            DB::transaction(fn () => DB::unprepared(file_get_contents(database_path('seeders/sql/sample-filipino-names.sql')).PHP_EOL
                .file_get_contents(database_path('seeders/sql/rename-sample-registry.sql'))));
            $this->fail('Changes outside name fields must roll back.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Changes outside sample name fields', $exception->getMessage());
        }

        $this->assertDatabaseHas('senior_citizens', ['registration_number' => 'SYN-00001', 'middle_name' => null, 'address' => 'SYNTHETIC ADDRESS - NOT A RESIDENCE']);
        $this->assertDatabaseCount('senior_citizens', 10000);
        $this->assertDatabaseCount('benefit_payments', 10000);
    }

    public function test_duplicate_dataset_is_rejected_before_inserting(): void
    {
        DB::table('programs')->insert(['name' => 'SYNTHETIC', 'agency' => 'SYNTHETIC', 'benefit_code' => 'synthetic-registry-2000-2026']);

        $this->artisan('app:seed-sample-registry', ['--commit' => true])->assertFailed();

        $this->assertDatabaseCount('programs', 1);
        $this->assertDatabaseCount('senior_citizens', 0);
    }

    private function prerequisites(): void
    {
        $this->seed([RoleSeeder::class, BarangaySeeder::class]);
        User::factory()->create(['role_id' => DB::table('roles')->where('name', 'ADMIN')->value('id')]);
    }

    private function sql(): string
    {
        return file_get_contents(database_path('seeders/sql/sample-filipino-names.sql')).PHP_EOL
            .file_get_contents(database_path('seeders/sql/sample-registry.sql'));
    }
}
