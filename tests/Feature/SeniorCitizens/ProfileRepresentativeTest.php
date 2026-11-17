<?php

namespace Tests\Feature\SeniorCitizens;

use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProfileRepresentativeTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['registry', 'senior-citizens.index', 'Back to masterlist'])]
    #[TestWith(['registrations', 'registrations.index', 'Back to registrations'])]
    public function test_profile_returns_to_the_source_list_with_its_filters(string $from, string $listRoute, string $label): void
    {
        $senior = $this->senior(['applicant_type' => 'SELF']);
        $filters = ['search' => 'Sample record', 'stage' => 'PENDING', 'sort' => 'last_name', 'direction' => 'asc', 'page' => 2];
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('senior-citizens.show', [$senior, 'from' => $from, ...$filters]));

        $response->assertSee($label);
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $link = (new \DOMXPath($document))->query('//main/div/header/a')->item(0);
        $this->assertSame(route($listRoute, $filters), $link->getAttribute('href'));
        $this->assertFalse($link->hasAttribute('x-on:click'));
    }

    public function test_profile_groups_location_fields_using_the_record_and_office_configuration_without_changing_data(): void
    {
        foreach (['municipality' => 'Synthetic municipality', 'province' => 'Synthetic province'] as $key => $value) {
            SystemConfiguration::forceCreate(['key' => $key, 'value' => $value]);
        }
        $senior = $this->senior(['applicant_type' => 'SELF', 'address' => '123 SYNTHETIC STREET']);
        $original = $senior->fresh()->getAttributes();
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('senior-citizens.show', $senior))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $header = $xpath->query('//main/div/header')->item(0);
        $card = $xpath->query('//div[@id="profile"]//section[.//h3[normalize-space()="Profile"]]')->item(0);

        $this->assertStringNotContainsString('Synthetic area', $header->textContent);
        $this->assertStringNotContainsString('Synthetic municipality', $header->textContent);
        $this->assertStringContainsString('Verified', $header->textContent);
        $fields = [];
        foreach ($xpath->query('.//dl/div', $card) as $field) {
            $fields[trim($field->getElementsByTagName('dt')->item(0)->textContent)] = trim($field->getElementsByTagName('dd')->item(0)->textContent);
        }
        $this->assertSame('Synthetic area', $fields['Barangay']);
        $this->assertSame('Synthetic municipality', $fields['Municipality']);
        $this->assertSame('Synthetic province', $fields['Province']);
        $this->assertSame('123 SYNTHETIC STREET', $fields['House No. / Street / Purok / Sitio / Subdivision']);
        $this->assertSame($original, $senior->fresh()->getAttributes());
        $this->assertDatabaseCount('senior_citizen_histories', 0);
    }

    public function test_staff_see_read_only_registration_representative_details_between_profile_and_record_actions(): void
    {
        $senior = $this->senior(['applicant_type' => 'REPRESENTATIVE']);
        $original = $senior->fresh()->getAttributes();
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('senior-citizens.show', $senior))->assertOk()
            ->assertSee('Authorized Representative Details')->assertSee('Synthetic Representative')
            ->assertSee('Synthetic relationship')->assertSee('+639170000001')
            ->assertSeeInOrder(['>Profile<', 'Authorized Representative Details', 'Record actions', 'Supporting Documents', 'Record activity'], false);

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $card = $xpath->query('//section[h3 or div/h3][.//h3[normalize-space()="Authorized Representative Details"]]')->item(0);
        $this->assertNotNull($card);
        $this->assertSame(3, $xpath->query('.//dt', $card)->length);
        $this->assertSame(0, $xpath->query('.//input | .//select | .//textarea | .//button', $card)->length);
        $this->assertSame($original, $senior->fresh()->getAttributes());
        $this->assertDatabaseCount('senior_citizens', 1);
        $this->assertDatabaseCount('senior_citizen_histories', 0);
    }

    #[TestWith(['SELF'])]
    #[TestWith(['UNKNOWN'])]
    #[TestWith([''])]
    public function test_other_applicant_types_do_not_render_a_card_even_with_representative_fields(string $type): void
    {
        $senior = $this->senior(['applicant_type' => $type]);
        $this->actingAs(User::factory()->create());

        $this->get(route('senior-citizens.show', $senior))->assertOk()
            ->assertDontSee('Authorized Representative Details')->assertDontSee('Synthetic Representative');

        $this->assertSame($type, $senior->fresh()->applicant_type);
    }

    public function test_details_and_visibility_follow_the_original_registration_after_multiple_profile_edits(): void
    {
        $senior = $this->senior(['applicant_type' => 'SELF', 'representative_name' => 'Synthetic Latest Representative']);
        $senior->histories()->create(['action' => 'updated', 'changes' => ['from' => [
            'applicant_type' => 'REPRESENTATIVE', 'representative_name' => 'Synthetic Original Representative',
            'representative_relationship' => 'Original relationship', 'representative_contact' => '+639170000002',
        ], 'to' => ['representative_name' => 'Synthetic Intermediate Representative']]]);
        $senior->histories()->create(['action' => 'updated', 'changes' => ['from' => [
            'applicant_type' => 'REPRESENTATIVE', 'representative_name' => 'Synthetic Intermediate Representative',
        ], 'to' => ['applicant_type' => 'SELF', 'representative_name' => 'Synthetic Latest Representative']]]);
        $original = $senior->fresh()->getAttributes();
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('senior-citizens.show', $senior))->assertOk();
        $this->assertSame('REPRESENTATIVE', $response->viewData('registrationApplicant')['applicant_type']);
        $response
            ->assertSee('Authorized Representative Details')->assertSee('Synthetic Original Representative')
            ->assertSee('Original relationship')->assertSee('+639170000002')
            ->assertDontSee('Synthetic Latest Representative')->assertDontSee('Synthetic Intermediate Representative');

        $this->assertSame($original, $senior->fresh()->getAttributes());
        $this->assertDatabaseCount('senior_citizen_histories', 2);
    }

    #[TestWith(['SELF'])]
    #[TestWith(['UNKNOWN'])]
    #[TestWith([null])]
    public function test_later_representative_edits_do_not_change_the_original_applicant_classification(?string $originalType): void
    {
        $senior = $this->senior(['applicant_type' => 'REPRESENTATIVE']);
        $senior->histories()->create(['action' => 'updated', 'changes' => [
            'from' => ['applicant_type' => $originalType], 'to' => ['applicant_type' => 'REPRESENTATIVE'],
        ]]);
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('senior-citizens.show', $senior))->assertOk()->assertDontSee('Authorized Representative Details');

        $this->assertSame($originalType, $response->viewData('registrationApplicant')['applicant_type']);
        $this->assertSame('REPRESENTATIVE', $senior->fresh()->applicant_type);
    }

    public function test_missing_representative_values_remain_unrecorded_instead_of_using_senior_details(): void
    {
        $senior = $this->senior(['applicant_type' => 'REPRESENTATIVE', 'representative_name' => null, 'representative_relationship' => null, 'representative_contact' => null]);
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('senior-citizens.show', $senior))->assertOk()->assertSee('Authorized Representative Details');

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(3, $xpath->query('//div[h3[normalize-space()="Authorized Representative Details"]]/following-sibling::dl//dd[normalize-space()="Not recorded"]')->length);
    }

    public function test_representative_details_are_escaped_when_rendered(): void
    {
        $senior = $this->senior([
            'applicant_type' => 'REPRESENTATIVE', 'representative_name' => '<script>name</script>',
            'representative_relationship' => '<script>relationship</script>', 'representative_contact' => '<script>contact</script>',
        ]);
        $this->actingAs(User::factory()->create());

        $this->get(route('senior-citizens.show', $senior))->assertOk()
            ->assertSee('<script>name</script>')->assertDontSee('<script>name</script>', false)
            ->assertSee('<script>relationship</script>')->assertDontSee('<script>relationship</script>', false)
            ->assertSee('<script>contact</script>')->assertDontSee('<script>contact</script>', false);
    }

    public function test_guests_and_inactive_accounts_cannot_read_representative_information(): void
    {
        $senior = $this->senior(['applicant_type' => 'REPRESENTATIVE']);

        $this->get(route('senior-citizens.show', $senior))->assertRedirectToRoute('login')->assertDontSee('Synthetic Representative');
        $this->actingAs(User::factory()->create(['is_active' => false]))
            ->get(route('senior-citizens.show', $senior))->assertRedirectToRoute('login')->assertDontSee('Synthetic Representative');
    }

    public function test_authorized_administrators_can_read_the_representative_card(): void
    {
        $senior = $this->senior(['applicant_type' => 'REPRESENTATIVE']);

        $this->actingAs(User::factory()->admin()->create())->get(route('senior-citizens.show', $senior))
            ->assertOk()->assertSee('Authorized Representative Details')->assertSee('Synthetic Representative');
    }

    private function senior(array $attributes): SeniorCitizen
    {
        $barangay = Barangay::create(['name' => 'Synthetic area', 'code' => 'SYN']);

        return SeniorCitizen::create([
            'barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => 'Senior',
            'birth_date' => '1950-01-01', 'sex' => 'FEMALE', 'address' => 'Synthetic address', 'status' => 'VERIFIED',
            'representative_name' => 'Synthetic Representative', 'representative_relationship' => 'Synthetic relationship',
            'representative_contact' => '+639170000001', ...$attributes,
        ]);
    }
}
