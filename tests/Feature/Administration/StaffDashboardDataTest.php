<?php

namespace Tests\Feature\Administration;

use App\Models\Barangay;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDashboardDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_empty_registry_has_zero_totals_and_no_invented_milestones(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk();

        $this->assertSame(0, $response->viewData('activeCount'));
        $this->assertSame(0, $response->viewData('barangayCount'));
        $this->assertSame(0, $response->viewData('milestoneCount'));
        $response->assertSee('No milestone programs configured');
    }

    public function test_coverage_counts_all_barangays_even_when_the_chart_is_limited(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (range(1, 10) as $index) {
            $barangay = Barangay::create(['name' => 'Synthetic area '.$index, 'code' => 'SYN-'.$index]);
            $this->senior($barangay, $index, '1948-01-01');
        }

        $response = $this->get(route('dashboard'))->assertOk();

        $this->assertSame(10, $response->viewData('barangayCount'));
        $this->assertCount(8, $response->viewData('barangays'));
    }

    public function test_milestone_totals_follow_the_active_program_rules(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 16)->startOfDay());
        $this->actingAs(User::factory()->create());
        $barangay = Barangay::create(['name' => 'Synthetic area', 'code' => 'SYN']);
        $this->senior($barangay, 1, '1942-01-01');
        $this->senior($barangay, 2, '1947-01-01');
        $program = Program::create(['name' => 'Synthetic milestone program', 'agency' => 'Test agency', 'status' => 'ACTIVE', 'benefit_rules' => ['cadence' => 'MILESTONE', 'milestones' => [85 => 1000]]]);

        $response = $this->get(route('dashboard'))->assertOk()->assertSee('Ages 85');

        $this->assertSame(1, $response->viewData('milestoneCount'));
        $program->update(['status' => 'UPCOMING']);
        $response = $this->get(route('dashboard'))->assertOk();
        $this->assertSame(0, $response->viewData('milestoneCount'));
    }

    private function senior(Barangay $barangay, int $index, string $birthDate): void
    {
        SeniorCitizen::create(['barangay_id' => $barangay->id, 'registration_number' => 'SC-SYN-'.$index, 'first_name' => 'Synthetic', 'last_name' => 'Applicant '.$index, 'birth_date' => $birthDate, 'sex' => 'FEMALE', 'address' => 'Test address', 'status' => 'VERIFIED']);
    }
}
