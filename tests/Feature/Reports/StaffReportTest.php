<?php

namespace Tests\Feature\Reports;

use App\Models\Barangay;
use App\Models\Role;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class StaffReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_exports_are_throttled_per_staff_account(): void
    {
        $this->actingAs(User::factory()->create());
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->get(route('reports.export', ['type' => 'masterlist']))->assertOk();
        }
        $this->get(route('reports.export', ['type' => 'masterlist']))->assertTooManyRequests();
        $this->flushSession();
        $this->actingAs(User::factory()->create())->get(route('reports.export', ['type' => 'masterlist']))->assertOk();
    }

    public function test_transferred_status_is_available_in_preview_and_export(): void
    {
        $barangay = Barangay::create(['name' => 'Synthetic barangay', 'code' => 'SYN']);
        foreach (['TRANSFERRED', 'VERIFIED'] as $status) {
            SeniorCitizen::create(['barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => $status, 'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Test address', 'status' => $status]);
        }
        $this->actingAs(User::factory()->create());

        $this->get(route('reports.workspace', ['type' => 'masterlist', 'status' => 'TRANSFERRED']))
            ->assertOk()->assertSee('Synthetic TRANSFERRED')->assertDontSee('Synthetic VERIFIED');
        $export = $this->get(route('reports.export', ['type' => 'masterlist', 'status' => 'TRANSFERRED']))->assertOk();

        $this->assertStringContainsString('Synthetic TRANSFERRED', $export->streamedContent());
        $this->assertStringNotContainsString('Synthetic VERIFIED', $export->streamedContent());
    }

    #[TestWith(['applications'])]
    #[TestWith(['payouts'])]
    #[TestWith(['proxy'])]
    public function test_an_end_date_can_be_used_without_a_start_date(string $type): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('reports.workspace', ['type' => $type, 'to' => '2026-10-16']))->assertOk();
        $this->get(route('reports.export', ['type' => $type, 'to' => '2026-10-16']))->assertOk();
    }

    public function test_a_date_range_cannot_end_before_it_starts(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson(route('reports.workspace', ['type' => 'payouts', 'from' => '2026-10-16', 'to' => '2026-10-15']))
            ->assertUnprocessable()->assertJsonValidationErrors('to');
    }

    public function test_preview_and_csv_export_apply_the_same_barangay_filter(): void
    {
        $first = Barangay::create(['name' => 'Poblacion', 'code' => 'POB']);
        $second = Barangay::create(['name' => 'Bagbaguin', 'code' => 'BAG']);
        foreach ([$first, $second] as $barangay) {
            SeniorCitizen::create(['barangay_id' => $barangay->id, 'first_name' => 'Test', 'last_name' => $barangay->name, 'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Test address']);
        }
        $role = Role::firstOrCreate(['name' => 'OSCA_STAFF'], ['description' => 'OSCA Staff']);
        $staff = User::create(['role_id' => $role->id, 'username' => 'reportuser', 'email' => 'report@example.test', 'password_hash' => 'password', 'first_name' => 'Test', 'last_name' => 'User', 'is_active' => true]);

        $this->actingAs($staff)->get(route('reports.workspace', ['type' => 'masterlist', 'barangay_id' => $first->id]))
            ->assertOk()->assertSee('Test Poblacion')->assertDontSee('Test Bagbaguin');

        $export = $this->get(route('reports.export', ['type' => 'masterlist', 'barangay_id' => $first->id]));
        $export->assertOk();
        $this->assertStringContainsString('Test Poblacion', $export->streamedContent());
        $this->assertStringNotContainsString('Test Bagbaguin', $export->streamedContent());
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.exported', 'user_id' => $staff->id]);
    }
}
