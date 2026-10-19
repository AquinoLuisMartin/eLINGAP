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
            'requirements_status' => 'REVIEW', 'requirements_checks' => ['identity' => false, 'residency' => false, 'authorization' => false], 'review_reason' => 'Synthetic document needs checking',
            'barangay_id' => $barangay->id,
            'first_name' => 'Juan',
            'middle_name' => 'Dela',
            'last_name' => 'Cruz',
            'birth_date' => '1950-01-01',
            'sex' => 'MALE',
            'civil_status' => 'MARRIED',
            'address' => 'Poblacion, Santa Maria, Bulacan',
            'contact_number' => '917-123-4567',
        ]);

        $seniorCitizen = SeniorCitizen::first();
        $response->assertRedirect(route('senior-citizens.show', $seniorCitizen));
        $this->assertSame('PENDING', $seniorCitizen->status->value);
        $this->assertSame('+639171234567', $seniorCitizen->contact_number);
        $this->assertSame('JUAN', $seniorCitizen->first_name);
        $this->assertSame('DELA', $seniorCitizen->middle_name);
        $this->assertSame('CRUZ', $seniorCitizen->last_name);
        $this->assertSame('POBLACION, SANTA MARIA, BULACAN', $seniorCitizen->address);
        $this->assertDatabaseHas('senior_citizen_histories', ['senior_citizen_id' => $seniorCitizen->id, 'action' => 'created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'senior_citizen.created', 'auditable_id' => $seniorCitizen->id]);
    }

    public function test_editing_legacy_representative_casing_keeps_existing_authorization_proof(): void
    {
        $staff = $this->user('OSCA_STAFF');
        $senior = $this->seniorCitizen();
        $senior->update(['applicant_type' => 'REPRESENTATIVE', 'representative_name' => 'Synthetic Representative',
            'representative_relationship' => 'Child', 'representative_contact' => '+639672348976']);
        $senior->documents()->create(['document_type' => 'AUTHORIZATION', 'path' => 'synthetic-proof.pdf',
            'original_name' => 'synthetic-proof.pdf', 'mime_type' => 'application/pdf', 'size' => 100, 'uploaded_by' => $staff->id]);

        $this->actingAs($staff)->get(route('senior-citizens.edit', $senior))->assertOk()
            ->assertSee('967-234-8976')->assertSee('+63');
        $this->put(route('senior-citizens.update', $senior), [
            ...$senior->only(['barangay_id', 'first_name', 'middle_name', 'last_name', 'name_suffix', 'sex', 'address', 'applicant_type', 'representative_name', 'representative_relationship']),
            'birth_date' => $senior->birth_date->format('Y-m-d'), 'contact_number' => '967-234-8976', 'representative_contact' => '967-234-8976',
        ])->assertSessionHasNoErrors()->assertRedirect(route('senior-citizens.show', $senior));

        $this->assertSame('SYNTHETIC REPRESENTATIVE', $senior->fresh()->representative_name);
        $this->assertSame('CHILD', $senior->fresh()->representative_relationship);
        $this->assertSame('+639672348976', $senior->fresh()->contact_number);
        $this->assertDatabaseCount('senior_citizen_documents', 1);
    }

    public function test_registration_requires_a_senior_age_and_valid_barangay(): void
    {
        $staff = $this->user('OSCA_STAFF');

        $this->actingAs($staff)->post(route('senior-citizens.store'), [
            'requirements_status' => 'REVIEW', 'requirements_checks' => ['identity' => false, 'residency' => false, 'authorization' => false], 'review_reason' => 'Synthetic document needs checking',
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
            'requirements_status' => 'REVIEW', 'requirements_checks' => ['identity' => false, 'residency' => false, 'authorization' => false], 'review_reason' => 'Synthetic document needs checking',
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
            'address' => 'Updated Address, Santa Maria', 'contact_number' => '09171234567',
        ]);

        $response->assertRedirect(route('senior-citizens.show', $seniorCitizen));
        $this->assertSame('MARIA UPDATED', $seniorCitizen->fresh()->first_name);

        $history = $seniorCitizen->histories()->where('action', 'updated')->first();
        $this->assertNotNull($history);
        $this->assertSame('Maria', $history->changes['from']['first_name']);
        $this->assertSame('MARIA UPDATED', $history->changes['to']['first_name']);

        $audit = AuditLog::where('auditable_id', $seniorCitizen->id)->where('action', 'senior_citizen.updated')->first();
        $this->assertNotNull($audit);
        $this->assertSame('Maria', $audit->old_values['first_name']);
        $this->assertSame('MARIA UPDATED', $audit->new_values['first_name']);
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

    public function test_existing_id_photo_remains_private_and_available_to_authorized_staff(): void
    {
        Storage::fake('local');
        $senior = $this->seniorCitizen();
        $staff = $this->user('OSCA_STAFF');

        $file = UploadedFile::fake()->createWithContent('legacy-photo.jpg', file_get_contents(base_path('tests/Fixtures/id-photo.jpg')));
        $path = $file->store('senior-photos', 'local');
        $senior->documents()->create(['document_type' => 'PHOTO', 'path' => $path, 'original_name' => 'legacy-photo.jpg', 'mime_type' => 'image/jpeg', 'size' => $file->getSize(), 'uploaded_by' => $staff->id]);
        $this->actingAs($staff);

        $photo = $senior->documents()->where('document_type', 'PHOTO')->firstOrFail();
        Storage::disk('local')->assertExists($photo->path);
        $this->get(route('senior-citizens.photo', $senior))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_guest_cannot_access_id_photo(): void
    {
        $senior = $this->seniorCitizen();

        $this->get(route('senior-citizens.photo', $senior))->assertRedirect(route('login'));
    }

    public function test_barangay_filter_limits_records_and_persists_across_pages(): void
    {
        $staff = $this->user('OSCA_STAFF');
        $otherSenior = $this->seniorCitizen();
        $barangay = Barangay::create(['name' => 'Test Barangay', 'code' => 'TEST']);
        for ($index = 1; $index <= 16; $index++) {
            $senior = $otherSenior->replicate();
            $senior->fill(['barangay_id' => $barangay->id, 'registration_number' => 'SC-FILTER-'.$index]);
            $senior->save();
        }

        $response = $this->actingAs($staff)->get(route('senior-citizens.index', ['barangay_id' => $barangay->id]));

        $response->assertOk()->assertViewHas('seniorCitizens', function ($records) use ($barangay) {
            $this->assertSame(16, $records->total());
            $this->assertSame([$barangay->id], $records->pluck('barangay_id')->unique()->values()->all());
            $this->assertStringContainsString('barangay_id='.$barangay->id, $records->nextPageUrl());

            return true;
        });
        $this->get($response->viewData('seniorCitizens')->nextPageUrl())
            ->assertViewHas('seniorCitizens', fn ($records) => $records->count() === 1 && $records->first()->barangay_id === $barangay->id);
        $this->get(route('senior-citizens.index', ['barangay_id' => '']))
            ->assertViewHas('seniorCitizens', fn ($records) => $records->total() === 17);
    }

    public function test_barangay_filter_rejects_unknown_barangay(): void
    {
        $staff = $this->user('OSCA_STAFF');

        $this->actingAs($staff)->getJson(route('senior-citizens.index', ['barangay_id' => 999]))
            ->assertUnprocessable()->assertJsonValidationErrors('barangay_id');
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
