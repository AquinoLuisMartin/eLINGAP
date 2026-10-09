<?php

namespace Tests\Feature\Programs;

use App\Models\Application;
use App\Models\Barangay;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BeneficiarySecurityTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['program'])]
    #[TestWith(['senior'])]
    #[TestWith(['pending'])]
    #[TestWith(['rejected'])]
    #[TestWith(['cancelled'])]
    #[TestWith(['approved'])]
    public function test_enrollment_only_accepts_an_approved_application_for_the_same_senior_and_program(string $case): void
    {
        $admin = User::factory()->admin()->create();
        $barangay = Barangay::create(['name' => 'Test barangay', 'code' => 'TEST']);
        $senior = SeniorCitizen::create([
            'barangay_id' => $barangay->id, 'registration_number' => 'SC-SECURITY-1',
            'first_name' => 'Synthetic', 'last_name' => 'Applicant', 'birth_date' => '1948-01-01',
            'sex' => 'FEMALE', 'address' => 'Test address', 'status' => 'VERIFIED',
        ]);
        $program = Program::create(['name' => 'Test assistance', 'agency' => 'OSCA', 'budget' => 10000, 'status' => 'ACTIVE']);
        $application = Application::create([
            'senior_citizen_id' => $senior->id, 'program_id' => $program->id,
            'application_number' => 'APP-SECURITY-1', 'applied_on' => '2026-10-01',
            'status' => in_array($case, ['pending', 'rejected', 'cancelled'], true) ? strtoupper($case) : 'APPROVED',
        ]);
        if ($case === 'program') {
            $other = Program::create(['name' => 'Other assistance', 'agency' => 'OSCA', 'budget' => 10000, 'status' => 'ACTIVE']);
            $application->update(['program_id' => $other->id]);
        }
        if ($case === 'senior') {
            $other = SeniorCitizen::create([
                ...$senior->only(['barangay_id', 'birth_date', 'sex', 'address', 'status']),
                'registration_number' => 'SC-SECURITY-2', 'first_name' => 'Other', 'last_name' => 'Applicant',
            ]);
            $application->update(['senior_citizen_id' => $other->id]);
        }

        $response = $this->actingAs($admin)->post(route('programs.beneficiaries.store', $program), [
            'program_id' => $program->id, 'senior_citizen_id' => $senior->id,
            'application_id' => $application->id, 'enrolled_on' => '2026-10-01',
        ]);

        if ($case === 'approved') {
            $response->assertRedirect();
            $this->assertDatabaseHas('beneficiaries', ['application_id' => $application->id, 'senior_citizen_id' => $senior->id, 'program_id' => $program->id]);
            $this->assertDatabaseHas('audit_logs', ['action' => 'beneficiary.enrolled', 'user_id' => $admin->id]);
        } else {
            $response->assertInvalid(['application_id' => 'The selected application id is invalid.']);
            $this->assertDatabaseEmpty('beneficiaries');
            $this->assertDatabaseEmpty('audit_logs');
        }
    }
}
