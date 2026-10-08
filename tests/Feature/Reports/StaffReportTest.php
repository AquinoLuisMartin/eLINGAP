<?php

namespace Tests\Feature\Reports;

use App\Models\Barangay;
use App\Models\Role;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_and_csv_export_apply_the_same_barangay_filter(): void
    {
        $first = Barangay::create(['name' => 'Poblacion', 'code' => 'POB']);
        $second = Barangay::create(['name' => 'Bagbaguin', 'code' => 'BAG']);
        foreach ([$first, $second] as $barangay) {
            SeniorCitizen::create(['barangay_id' => $barangay->id, 'registration_number' => 'SC-'.$barangay->code, 'first_name' => 'Test', 'last_name' => $barangay->name, 'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Test address']);
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
