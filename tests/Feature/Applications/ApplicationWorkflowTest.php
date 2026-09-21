<?php

namespace Tests\Feature\Applications;

use App\Models\Application;
use App\Models\Barangay;
use App\Models\Program;
use App\Models\Role;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_submit_and_review_an_application(): void
    {
        $staff = $this->user('OSCA_STAFF');
        $seniorCitizen = $this->seniorCitizen();
        $seniorCitizen->update(['status' => 'VERIFIED']);
        $program = Program::create(['name' => 'Medical Assistance', 'agency' => 'OSCA', 'budget' => 100000, 'status' => 'ACTIVE']);

        $response = $this->actingAs($staff)->post(route('applications.store'), [
            'senior_citizen_id' => $seniorCitizen->id,
            'program_id' => $program->id,
            'applied_on' => '2026-09-21',
        ]);

        $application = Application::first();
        $response->assertRedirect(route('applications.show', $application));
        $this->assertDatabaseHas('application_status_histories', ['application_id' => $application->id, 'to_status' => 'PENDING']);

        $this->actingAs($staff)->patch(route('applications.status.update', $application), [
            'status' => 'APPROVED',
            'remarks' => 'Documents verified.',
        ])->assertRedirect();

        $this->assertSame('APPROVED', $application->fresh()->status->value);
        $this->assertDatabaseHas('application_status_histories', ['application_id' => $application->id, 'from_status' => 'PENDING', 'to_status' => 'APPROVED']);
    }

    public function test_application_creation_requires_a_verified_senior_citizen(): void
    {
        $staff = $this->user('OSCA_STAFF');
        $seniorCitizen = $this->seniorCitizen();
        $program = Program::create(['name' => 'Medical Assistance', 'agency' => 'OSCA', 'budget' => 100000, 'status' => 'ACTIVE']);

        $this->actingAs($staff)->get(route('applications.create'))->assertOk()->assertDontSee($seniorCitizen->full_name);
        $this->assertSame(0, Application::count());
    }

    private function user(string $role): User
    {
        $roleRecord = Role::firstOrCreate(['name' => $role], ['description' => $role]);

        return User::create(['role_id' => $roleRecord->id, 'username' => strtolower($role).'user', 'email' => strtolower($role).'@example.test', 'password_hash' => 'password', 'first_name' => 'Test', 'last_name' => 'User', 'is_active' => true]);
    }

    private function seniorCitizen(): SeniorCitizen
    {
        $barangay = Barangay::create(['name' => 'Poblacion', 'code' => 'POB']);

        return SeniorCitizen::create(['barangay_id' => $barangay->id, 'registration_number' => 'SC-TEST-001', 'first_name' => 'Maria', 'last_name' => 'Santos', 'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Poblacion']);
    }
}
