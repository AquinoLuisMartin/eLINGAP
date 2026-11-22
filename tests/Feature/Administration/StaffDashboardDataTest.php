<?php

namespace Tests\Feature\Administration;

use App\Models\Barangay;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\User;
use Database\Seeders\BenefitProgramSeeder;
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
        $response->assertSee('No verified seniors with a barangay recorded yet.');
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
        $this->assertSame(2, (int) $response->viewData('otherBarangays')->sum('total'));
        $response->assertSee('Other barangays')
            ->assertSee('20.0%')
            ->assertSee('View counts for all 10 barangays');
    }

    public function test_age_distribution_counts_birthdays_in_the_correct_brackets(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 22)->startOfDay());
        $barangay = Barangay::create(['name' => 'Synthetic area', 'code' => 'SYN']);

        foreach (['1966-11-23', '1966-11-22', '1956-11-23', '1956-11-22', '1946-11-23', '1946-11-22', '1936-11-23', '1936-11-22'] as $index => $birthDate) {
            $this->senior($barangay, $index, $birthDate);
        }

        $response = $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk();

        $this->assertSame([2, 2, 2, 1], $response->viewData('ageGroups')->values()->all());
    }

    public function test_milestone_totals_follow_the_active_program_rules(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 16)->startOfDay());
        $this->actingAs(User::factory()->create());
        $this->seed(BenefitProgramSeeder::class);
        $barangay = Barangay::create(['name' => 'Synthetic area', 'code' => 'SYN']);
        $this->senior($barangay, 1, '1942-01-01');
        $this->senior($barangay, 2, '1947-01-01');
        $program = Program::where('benefit_code', 'national-milestones')->sole();
        $program->update(['status' => 'ACTIVE', 'benefit_rules' => ['cadence' => 'MILESTONE', 'milestones' => [85 => 1000]]]);

        $response = $this->get(route('dashboard'))->assertOk()->assertSee('Ages 85');

        $this->assertSame(1, $response->viewData('milestoneCount'));
        $program->update(['status' => 'UPCOMING']);
        $response = $this->get(route('dashboard'))->assertOk();
        $this->assertSame(0, $response->viewData('milestoneCount'));
    }

    private function senior(Barangay $barangay, int $index, string $birthDate): void
    {
        SeniorCitizen::create(['barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => 'Applicant '.$index, 'birth_date' => $birthDate, 'sex' => 'FEMALE', 'address' => 'Test address', 'status' => 'VERIFIED']);
    }
}
