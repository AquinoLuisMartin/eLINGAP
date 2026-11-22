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

    #[TestWith(['masterlist'])]
    #[TestWith(['beneficiaries'])]
    public function test_download_actions_submit_the_current_filter_form_instead_of_saved_query_links(string $type): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('reports.workspace', ['type' => $type]));

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $form = $xpath->query('//form[@id="report-filters"]')->item(0);
        $this->assertNotNull($form);
        $this->assertSame('GET', $form->getAttribute('method'));
        $this->assertSame(route('reports.workspace', $type), $form->getAttribute('action'));
        $this->assertSame(0, $xpath->query('.//button[@type="submit"]', $form)->length);
        $response->assertDontSee('Apply filters')->assertDontSee('Filters apply automatically.')->assertDontSee('Use Excel (.xlsx) to preserve OSCA IDs as text.');
        $this->assertSame(1, $xpath->query('.//select[@name="barangay_id"]', $form)->length);
        $this->assertSame(2, $xpath->query('.//input[@name="age_min" or @name="age_max"]', $form)->length);
        foreach (['xlsx', 'csv', 'pdf'] as $format) {
            $button = $xpath->query('//button[@name="format" and @value="'.$format.'"]')->item(0);
            $this->assertNotNull($button);
            $this->assertSame('report-filters', $button->getAttribute('form'));
            $this->assertSame('submit', $button->getAttribute('type'));
            $this->assertSame(route('reports.export', $type), $button->getAttribute('formaction'));
        }
    }

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
            ->assertOk()->assertSee('>TRANSFERRED<', false)->assertDontSee('>VERIFIED<', false);
        $export = $this->get(route('reports.export', ['type' => 'masterlist', 'status' => 'TRANSFERRED']))->assertOk();

        $this->assertStringContainsString(',TRANSFERRED,Synthetic,', $export->streamedContent());
        $this->assertStringNotContainsString(',VERIFIED,Synthetic,', $export->streamedContent());
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
        $staff = User::create(['role_id' => $role->id, 'employee_id' => '2026-0007', 'email' => 'report@example.test', 'password_hash' => 'password', 'first_name' => 'Test', 'last_name' => 'User', 'is_active' => true]);

        $this->actingAs($staff)->get(route('reports.workspace', ['type' => 'masterlist', 'barangay_id' => $first->id]))
            ->assertOk()->assertSee('>Poblacion</td>', false)->assertDontSee('>Bagbaguin</td>', false);

        $export = $this->get(route('reports.export', ['type' => 'masterlist', 'barangay_id' => $first->id]));
        $export->assertOk();
        $this->assertStringContainsString(',Poblacion,Test,', $export->streamedContent());
        $this->assertStringNotContainsString(',Bagbaguin,Test,', $export->streamedContent());
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.exported', 'user_id' => $staff->id]);
    }
}
