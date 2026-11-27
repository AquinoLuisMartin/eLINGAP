<?php

namespace Tests\Feature\SeniorCitizens;

use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class EditProfileFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_form_has_only_male_and_female_and_does_not_expose_registration_fields(): void
    {
        $senior = $this->senior('REPRESENTATIVE');
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('senior-citizens.edit', $senior))->assertOk()
            ->assertDontSee('Applicant and authorized representative')->assertDontSee('OSCA ID number')
            ->assertSee('Confirm profile changes')->assertSee('No changes detected')->assertSee('Confirm changes');
        $xpath = $this->xpath($response->getContent());

        $options = $xpath->query('//main/form//select[@name="sex"]/option');
        $this->assertSame(['MALE', 'FEMALE'], array_map(fn ($option) => $option->getAttribute('value'), iterator_to_array($options)));
        $suffixes = $xpath->query('//main/form//select[@name="name_suffix"]/option');
        $this->assertSame(['', 'Jr.', 'Sr.', 'II', 'III', 'IV', 'V'], array_map(fn ($option) => $option->getAttribute('value'), iterator_to_array($suffixes)));
        foreach (['applicant_type', 'representative_name', 'representative_relationship', 'representative_contact', 'authorization_file'] as $field) {
            $this->assertSame(0, $xpath->query('//main/form//*[@name="'.$field.'"]')->length);
        }
        $this->assertSame(0, $xpath->query('//main/form//*[@id="osca_id_number"]')->length);
        $this->assertSame('FEMALE', $xpath->query('//main/form//select[@name="sex"]/option[@selected]')->item(0)->getAttribute('value'));
    }

    public function test_review_baseline_uses_the_saved_record_after_validation_redirects(): void
    {
        $senior = $this->senior('REPRESENTATIVE');
        $this->actingAs(User::factory()->create());

        $response = $this->withSession(['_old_input' => ['first_name' => 'UNSAVED']])
            ->get(route('senior-citizens.edit', $senior))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $baseline = $xpath->query('//main/form')->item(0)->getAttribute('x-data');

        $this->assertStringContainsString('SYNTHETIC', $baseline);
        $this->assertStringNotContainsString('UNSAVED', $baseline);
        $this->assertStringNotContainsString('representative_name', $baseline);
        $this->assertStringNotContainsString('osca_id_number', $baseline);
        $this->assertSame('UNSAVED', $xpath->query('//input[@name="first_name"]')->item(0)->getAttribute('value'));
        $this->assertSame('SYNTHETIC', $senior->fresh()->first_name);
        $this->assertDatabaseCount('senior_citizen_histories', 0);
    }

    #[TestWith(['REPRESENTATIVE', true])]
    #[TestWith(['REPRESENTATIVE', false])]
    #[TestWith(['SELF', false])]
    #[TestWith(['UNKNOWN', false])]
    public function test_saving_visible_profile_fields_preserves_registration_data_and_authorization_documents(string $type, bool $hasProof): void
    {
        Storage::fake('local');
        $senior = $this->senior($type);
        $staff = User::factory()->create();
        $saved = $senior->only(['osca_id_number', 'applicant_type', 'representative_name', 'representative_relationship', 'representative_contact']);
        if ($hasProof) {
            Storage::disk('local')->put('senior-documents/original-proof.pdf', 'Synthetic proof');
            $senior->documents()->create([
                'document_type' => 'AUTHORIZATION', 'path' => 'senior-documents/original-proof.pdf', 'original_name' => 'original-proof.pdf',
                'mime_type' => 'application/pdf', 'size' => 15, 'uploaded_by' => $staff->id,
            ]);
        }
        $this->actingAs($staff);

        $this->put(route('senior-citizens.update', $senior), [...$this->visibleFields($senior), 'address' => 'Updated synthetic address'])
            ->assertSessionHasNoErrors()->assertRedirectToRoute('senior-citizens.show', $senior);

        $this->assertSame($saved, $senior->fresh()->only(array_keys($saved)));
        $this->assertSame('UPDATED SYNTHETIC ADDRESS', $senior->fresh()->address);
        $this->assertSame(['address' => 'UPDATED SYNTHETIC ADDRESS'], $senior->histories()->sole()->getAttribute('changes')['to']);
        $this->assertDatabaseCount('senior_citizen_documents', $hasProof ? 1 : 0);
        if ($hasProof) {
            Storage::disk('local')->assertExists('senior-documents/original-proof.pdf');
        }
    }

    public function test_an_existing_other_value_is_not_silently_selected_as_male(): void
    {
        $senior = $this->senior('SELF');
        $senior->update(['sex' => 'OTHER']);
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('senior-citizens.edit', $senior))->assertOk();
        $xpath = $this->xpath($response->getContent());

        $this->assertSame('', $xpath->query('//main/form//select[@name="sex"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertSame('OTHER', $senior->fresh()->sex);
    }

    public function test_other_is_rejected_on_profile_update_without_changing_saved_data(): void
    {
        $senior = $this->senior('REPRESENTATIVE');
        $original = $senior->fresh()->getAttributes();
        $this->actingAs(User::factory()->create());

        $response = $this->putJson(route('senior-citizens.update', $senior), [...$this->visibleFields($senior), 'sex' => 'OTHER'])
            ->assertUnprocessable()->assertJsonValidationErrors('sex');

        $this->assertSame('The selected sex is invalid.', $response->json('errors.sex.0'));
        $this->assertSame($original, $senior->fresh()->getAttributes());
    }

    private function senior(string $type): SeniorCitizen
    {
        $barangay = Barangay::create(['name' => 'Synthetic area', 'code' => 'SYN']);

        return SeniorCitizen::create([
            'barangay_id' => $barangay->id, 'first_name' => 'SYNTHETIC', 'last_name' => 'SENIOR',
            'birth_date' => '1950-01-01', 'sex' => 'FEMALE', 'address' => 'SYNTHETIC ADDRESS',
            'contact_number' => '+639170000001', 'status' => 'VERIFIED', 'osca_id_number' => 'SYN-0001',
            'applicant_type' => $type, 'representative_name' => 'Synthetic Original Representative',
            'representative_relationship' => 'Synthetic relationship', 'representative_contact' => '+639170000002',
        ]);
    }

    private function visibleFields(SeniorCitizen $senior): array
    {
        return [
            ...$senior->only(['barangay_id', 'first_name', 'middle_name', 'last_name', 'name_suffix', 'sex', 'civil_status', 'address', 'contact_number']),
            'birth_date' => $senior->birth_date->format('Y-m-d'),
        ];
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);

        return new \DOMXPath($document);
    }
}
