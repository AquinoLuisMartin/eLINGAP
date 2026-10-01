<?php

namespace Tests\Feature\SeniorCitizens;

use App\Models\Barangay;
use App\Models\Role;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeniorCitizenManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_register_a_senior_citizen_and_records_are_audited(): void
    {
        $staff = $this->user('OSCA_STAFF');
        $barangay = Barangay::create(['name' => 'Poblacion', 'code' => 'POB']);

        $response = $this->actingAs($staff)->post(route('senior-citizens.store'), [
            'barangay_id' => $barangay->id,
            'first_name' => 'Juan',
            'middle_name' => 'Dela',
            'last_name' => 'Cruz',
            'birth_date' => '1950-01-01',
            'sex' => 'MALE',
            'civil_status' => 'MARRIED',
            'address' => 'Poblacion, Santa Maria, Bulacan',
            'contact_number' => '09171234567',
        ]);

        $seniorCitizen = SeniorCitizen::first();
        $response->assertRedirect(route('senior-citizens.show', $seniorCitizen));
        $this->assertSame('PENDING', $seniorCitizen->status->value);
        $this->assertDatabaseHas('senior_citizen_histories', ['senior_citizen_id' => $seniorCitizen->id, 'action' => 'created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'senior_citizen.created', 'auditable_id' => $seniorCitizen->id]);
    }

    public function test_registration_requires_a_senior_age_and_valid_barangay(): void
    {
        $staff = $this->user('OSCA_STAFF');

        $this->actingAs($staff)->post(route('senior-citizens.store'), [
            'barangay_id' => 999,
            'first_name' => 'Young',
            'last_name' => 'Applicant',
            'birth_date' => now()->subYears(30)->toDateString(),
            'sex' => 'MALE',
            'address' => 'Address',
        ])->assertSessionHasErrors(['barangay_id', 'birth_date']);

        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_staff_cannot_archive_a_senior_citizen(): void
    {
        $staff = $this->user('OSCA_STAFF');
        $seniorCitizen = $this->seniorCitizen();

        $this->actingAs($staff)
            ->delete(route('administration.senior-citizens.destroy', $seniorCitizen))
            ->assertForbidden();

        $this->assertSame('PENDING', $seniorCitizen->fresh()->status->value);
    }

    public function test_admin_can_archive_a_senior_citizen(): void
    {
        $admin = $this->user('ADMIN');
        $seniorCitizen = $this->seniorCitizen();

        $this->actingAs($admin)
            ->delete(route('administration.senior-citizens.destroy', $seniorCitizen))
            ->assertRedirect(route('senior-citizens.index'));

        $this->assertSame('ARCHIVED', $seniorCitizen->fresh()->status->value);
    }

    public function test_staff_can_update_a_senior_citizen_and_changes_are_audited(): void
    {
        $staff = $this->user('OSCA_STAFF');
        $seniorCitizen = $this->seniorCitizen();

        $response = $this->actingAs($staff)->put(route('senior-citizens.update', $seniorCitizen), [
            'barangay_id' => $seniorCitizen->barangay_id,
            'first_name' => 'Maria Updated',
            'last_name' => 'Santos',
            'birth_date' => '1948-01-01',
            'sex' => 'FEMALE',
            'address' => 'Updated Address, Santa Maria',
        ]);

        $response->assertRedirect(route('senior-citizens.show', $seniorCitizen));
        $this->assertSame('Maria Updated', $seniorCitizen->fresh()->first_name);

        $history = $seniorCitizen->histories()->where('action', 'updated')->first();
        $this->assertNotNull($history);
        $this->assertSame('Maria', $history->changes['from']['first_name']);
        $this->assertSame('Maria Updated', $history->changes['to']['first_name']);

        $audit = \App\Models\AuditLog::where('auditable_id', $seniorCitizen->id)->where('action', 'senior_citizen.updated')->first();
        $this->assertNotNull($audit);
        $this->assertSame('Maria', $audit->old_values['first_name']);
        $this->assertSame('Maria Updated', $audit->new_values['first_name']);
    }

    private function user(string $role): User
    {
        $roleRecord = Role::firstOrCreate(['name' => $role], ['description' => $role]);

        return User::create([
            'role_id' => $roleRecord->id,
            'username' => strtolower($role).'user',
            'email' => strtolower($role).'@example.test',
            'password_hash' => 'password',
            'first_name' => 'Test',
            'last_name' => 'User',
            'is_active' => true,
        ]);
    }

    private function seniorCitizen(): SeniorCitizen
    {
        $barangay = Barangay::create(['name' => 'Poblacion', 'code' => 'POB']);

        return SeniorCitizen::create([
            'barangay_id' => $barangay->id,
            'registration_number' => 'SC-TEST-001',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '1948-01-01',
            'sex' => 'FEMALE',
            'address' => 'Poblacion, Santa Maria, Bulacan',
        ]);
    }
}
