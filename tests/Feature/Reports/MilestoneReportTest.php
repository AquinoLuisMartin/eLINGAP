<?php

namespace Tests\Feature\Reports;

use App\Models\Barangay;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MilestoneReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_filters_and_export_follow_configured_milestone_ages(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 16)->startOfDay());
        $this->actingAs(User::factory()->create());
        Program::create(['name' => 'Synthetic milestone program', 'agency' => 'Synthetic office', 'status' => 'ACTIVE', 'benefit_rules' => ['milestones' => [85 => 1000, 95 => 2000]]]);
        $barangay = Barangay::create(['name' => 'Synthetic area', 'code' => 'SYN']);
        foreach (['1941-10-16' => 'Included', '1942-10-16' => 'Excluded'] as $date => $name) {
            SeniorCitizen::create(['barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => $name, 'birth_date' => $date, 'sex' => 'FEMALE', 'address' => 'Test address', 'status' => 'VERIFIED']);
        }

        $this->get(route('reports.workspace', ['type' => 'milestones']))->assertOk()
            ->assertSee('85+')->assertSee('95+')->assertSee('Synthetic Included')->assertDontSee('Synthetic Excluded');
        $export = $this->get(route('reports.export', ['type' => 'milestones', 'milestone' => 85]))->assertOk();

        $this->assertStringContainsString('Synthetic Included', $export->streamedContent());
        $this->assertStringNotContainsString('Synthetic Excluded', $export->streamedContent());
        $this->getJson(route('reports.workspace', ['type' => 'milestones', 'milestone' => 80]))
            ->assertUnprocessable()->assertJsonValidationErrors('milestone');
    }

    public function test_no_milestone_programs_produces_an_empty_report(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('reports.workspace', ['type' => 'milestones']))->assertOk()
            ->assertSee('No active milestones configured')->assertSee('No records match these filters.');

        $this->assertSame(0, $response->viewData('records')->total());
    }
}
