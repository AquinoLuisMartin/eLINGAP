<?php

namespace Tests\Feature\SeniorCitizens;

use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Role;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $this->assertSame('+639171234567', $seniorCitizen->contact_number);
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

    public function test_registration_rejects_duplicate_identity_and_invalid_mobile(): void
    {
        $senior = $this->seniorCitizen();
        $staff = $this->user('OSCA_STAFF');

        $this->actingAs($staff)->post(route('senior-citizens.store'), [
            'barangay_id' => $senior->barangay_id, 'first_name' => 'Maria', 'last_name' => 'Santos',
            'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Test address', 'contact_number' => '12345',
        ])->assertSessionHasErrors(['first_name', 'contact_number']);

        $this->assertSame(1, SeniorCitizen::count());
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

        $audit = AuditLog::where('auditable_id', $seniorCitizen->id)->where('action', 'senior_citizen.updated')->first();
        $this->assertNotNull($audit);
        $this->assertSame('Maria', $audit->old_values['first_name']);
        $this->assertSame('Maria Updated', $audit->new_values['first_name']);
    }

    public function test_death_declaration_requires_valid_date_and_document_and_blocks_edits(): void
    {
        Storage::fake('local');
        $senior = $this->seniorCitizen();
        $senior->update(['osca_id_number' => 'OSCA-TEST-1', 'status' => 'VERIFIED']);
        $staff = $this->user('OSCA_STAFF');

        $this->actingAs($staff)->post(route('senior-citizens.death.store', $senior), [
            'died_on' => '1940-01-01', 'confirmation' => 'OSCA-TEST-1', 'death_document' => UploadedFile::fake()->create('certificate.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('died_on');

        $this->post(route('senior-citizens.death.store', $senior), [
            'died_on' => '2026-01-01', 'confirmation' => 'OSCA-TEST-1', 'death_document' => UploadedFile::fake()->create('certificate.pdf', 10, 'application/pdf'),
        ])->assertRedirect();

        $this->assertSame('DECEASED', $senior->fresh()->status->value);
        $this->assertDatabaseHas('senior_citizen_documents', ['senior_citizen_id' => $senior->id, 'document_type' => 'DEATH_CERTIFICATE']);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $senior->id, 'action' => 'senior_citizen.deceased_declared']);
        $this->get(route('senior-citizens.edit', $senior))->assertForbidden();
    }

    public function test_id_photo_is_private_and_available_to_authorized_staff(): void
    {
        Storage::fake('local');
        $senior = $this->seniorCitizen();
        $staff = $this->user('OSCA_STAFF');

        $this->actingAs($staff)->put(route('senior-citizens.update', $senior), [
            'barangay_id' => $senior->barangay_id, 'first_name' => $senior->first_name,
            'last_name' => $senior->last_name, 'birth_date' => $senior->birth_date->format('Y-m-d'),
            'sex' => $senior->sex, 'address' => $senior->address,
            'photo' => UploadedFile::fake()->createWithContent('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+nmfwAAAAASUVORK5CYII=')),
        ])->assertRedirect();

        $photo = $senior->documents()->where('document_type', 'PHOTO')->firstOrFail();
        Storage::disk('local')->assertExists($photo->path);
        $this->get(route('senior-citizens.photo', $senior))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_guest_cannot_access_id_photo(): void
    {
        $senior = $this->seniorCitizen();

        $this->get(route('senior-citizens.photo', $senior))->assertRedirect(route('login'));
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
