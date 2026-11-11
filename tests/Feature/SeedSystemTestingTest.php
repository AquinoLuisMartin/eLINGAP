<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SeedSystemTestingTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_does_not_insert_records(): void
    {
        $this->artisan('app:seed-system-testing')->expectsOutputToContain('No changes made')->assertSuccessful();

        $this->assertDatabaseCount('roles', 0);
        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_reference_only_supplies_roles_without_mock_records(): void
    {
        $this->artisan('app:seed-system-testing', ['--commit' => true, '--reference-only' => true])->assertSuccessful();

        $this->assertDatabaseHas('roles', ['name' => 'ADMIN']);
        $this->assertDatabaseHas('roles', ['name' => 'OSCA_STAFF']);
        $this->assertDatabaseCount('barangays', 24);
        $this->assertDatabaseCount('programs', 9);
        $this->assertDatabaseCount('senior_citizens', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_mock_data_can_be_reapplied_without_resetting_records_or_sending_notifications(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $original = $user->refresh()->getAttributes();
        Http::preventStrayRequests();
        Queue::fake();
        $this->artisan('app:seed-system-testing', ['--commit' => true])->assertSuccessful();
        SeniorCitizen::query()->where('last_name', 'TEST SENIOR 01')->update(['status' => 'ARCHIVED']);
        Application::query()->where('application_number', 'MOCK-SYSTEM-13')->update(['status' => 'REJECTED']);

        $this->artisan('app:seed-system-testing', ['--commit' => true])->assertSuccessful();

        $this->assertDatabaseCount('senior_citizens', 24);
        $this->assertDatabaseCount('beneficiaries', 12);
        $this->assertDatabaseCount('applications', 6);
        $this->assertDatabaseCount('programs', 10);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($original, $user->fresh()->getAttributes());
        $this->assertDatabaseHas('senior_citizens', ['last_name' => 'TEST SENIOR 01', 'status' => 'ARCHIVED', 'contact_number' => null]);
        $this->assertDatabaseHas('applications', ['application_number' => 'MOCK-SYSTEM-13', 'status' => 'REJECTED']);
        $this->assertDatabaseCount('sms_messages', 0);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_production_requires_explicit_force(): void
    {
        $this->app->instance('env', 'production');

        $this->artisan('app:seed-system-testing', ['--commit' => true])->assertFailed();

        $this->assertDatabaseCount('roles', 0);
        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_explicit_force_supports_a_production_configured_testing_deployment(): void
    {
        $this->app->instance('env', 'production');

        $this->artisan('app:seed-system-testing', ['--commit' => true, '--force' => true])->assertSuccessful();

        $this->assertDatabaseHas('roles', ['name' => 'ADMIN']);
        $this->assertDatabaseHas('roles', ['name' => 'OSCA_STAFF']);
        $this->assertDatabaseCount('senior_citizens', 24);
        $this->assertDatabaseCount('users', 0);
    }
}
