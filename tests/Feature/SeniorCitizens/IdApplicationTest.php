<?php

namespace Tests\Feature\SeniorCitizens;

use App\Livewire\Registrations\IdApplication;
use App\Livewire\Registrations\Index;
use App\Livewire\SeniorCitizens\ApplicationLauncher;
use App\Models\Barangay;
use App\Models\IdType;
use App\Models\SeniorCitizen;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\SeniorCitizens\IdOfficials;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class IdApplicationTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_visible_fields_can_reach_confirmation_and_save_without_internal_form_keys(bool $modal): void
    {
        $this->actingAs(User::factory()->create());
        $input = $this->input();
        $component = $this->application($modal)
            ->set('form.first_name', $input['first_name'])->set('form.last_name', $input['last_name'])
            ->set('form.birth_date', $input['birth_date'])->set('form.sex', $input['sex'])
            ->call('next')->assertSet('step', 2)
            ->set('form.barangay_id', $input['barangay_id'])->set('form.contact_number', $input['contact_number'])
            ->set('form.address', $input['address'])->call('next')
            ->assertHasNoErrors()->assertSet('step', 3)->assertSee('Application summary')->assertSee('Save Registration')->assertSee('Save & Prepare ID');

        $this->assertDatabaseEmpty('senior_citizens');
        $component->set('informationReviewed', true)->call('submit')->assertHasNoErrors();

        $senior = SeniorCitizen::sole();
        $this->assertSame('VERIFIED', $senior->status->value);
        $this->assertSame('SYNTHETIC', $senior->first_name);
        $this->assertSame($input['barangay_id'], $senior->barangay_id);
        $component->assertRedirect(route('senior-id.show', [$senior, 'from' => 'registry']));
    }

    public function test_address_step_displays_stored_locality_as_readonly_fields(): void
    {
        $this->actingAs(User::factory()->create());
        SystemConfiguration::forceCreate(['key' => 'municipality', 'value' => 'Santa Maria']);
        SystemConfiguration::forceCreate(['key' => 'province', 'value' => 'Bulacan']);

        $this->application()->set('form', $this->input())->call('next')
            ->assertSeeHtml('id="locality-municipality" value="Santa Maria" readonly')
            ->assertSeeHtml('id="locality-province" value="Bulacan" readonly')
            ->assertSeeHtml('readonly disabled tabindex="-1"')
            ->assertDontSee('Enter 10 digits starting with 9. The +63 country code is included.');
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_confirmation_displays_all_entered_details_and_complete_address(bool $modal): void
    {
        $this->actingAs(User::factory()->create());
        SystemConfiguration::forceCreate(['key' => 'municipality', 'value' => 'Synthetic Municipality']);
        SystemConfiguration::forceCreate(['key' => 'province', 'value' => 'Synthetic Province']);
        $input = [...$this->input(), 'middle_name' => 'Middle', 'name_suffix' => 'III', 'civil_status' => 'MARRIED',
            'address' => 'Unit 123 Synthetic Street, Purok Four, Synthetic Subdivision'];

        $this->withIdentityDocuments(Livewire::test(IdApplication::class, ['modal' => $modal]))->set('form', $input)
            ->call('next')->call('next')->call('next')->assertHasNoErrors()->assertSet('step', 3)
            ->assertSee('SYNTHETIC')->assertSee('APPLICANT')->assertSee('MIDDLE')->assertSee('III')
            ->assertSee('1950-01-01')->assertSee('FEMALE')->assertSee('MARRIED')->assertSee('Mobile number')->assertSee('(+63) 917-123-4567')
            ->assertDontSee('Senior mobile number')
            ->assertSee('UNIT 123 SYNTHETIC STREET, PUROK FOUR, SYNTHETIC SUBDIVISION, SYNTHETIC BARANGAY, SYNTHETIC MUNICIPALITY, SYNTHETIC PROVINCE')
            ->assertSee('Birth Certificate / Proof of Age')->assertSee('Valid Government-Issued ID')
            ->assertSee('Checked')->assertSee('Not applicable')->assertDontSee('Authorized Representative Information');

        $this->assertDatabaseEmpty('senior_citizens');
    }

    public function test_confirmation_shows_missing_optional_values_and_escapes_address_text(): void
    {
        $this->actingAs(User::factory()->create());
        $input = [...$this->input(), 'address' => '<script>alert("synthetic")</script>'];

        $this->application()->set('form', $input)->call('next')->call('next')->assertHasNoErrors()->assertSet('step', 3)
            ->assertSee('Not provided')->assertSee('Not configured')
            ->assertSee('&lt;SCRIPT&gt;ALERT(&quot;SYNTHETIC&quot;)&lt;/SCRIPT&gt;', false)
            ->assertDontSee('<SCRIPT>', false);
    }

    #[TestWith(['917-123-4567'])]
    #[TestWith(['9171234567'])]
    #[TestWith(['+639171234567'])]
    public function test_confirmation_includes_the_country_code_for_supported_mobile_formats(string $number): void
    {
        $this->actingAs(User::factory()->create());
        $input = [...$this->input(), 'contact_number' => $number];

        $this->application()->set('form', $input)->call('next')->assertSet('step', 2)
            ->assertSee('Mobile number')->assertDontSee('Senior mobile number')
            ->call('next')->assertHasNoErrors()->assertSet('step', 3)
            ->assertSee('(+63) 917-123-4567')->assertSet('form.contact_number', $number);
    }

    public function test_invalid_mobile_fields_preserve_all_entered_registration_details(): void
    {
        $this->actingAs(User::factory()->create());
        $input = [...$this->input(), 'requirements_checks' => ['identity' => true, 'residency' => true, 'authorization' => true],
            'contact_number' => '917-12', 'representative_name' => 'Synthetic Representative',
            'representative_relationship' => 'Child', 'representative_contact' => '918-12'];

        $this->application()->set('form', $input)->call('next')->call('next')
            ->assertSet('step', 2)->assertHasErrors(['form.contact_number', 'form.representative_contact'])
            ->assertSet('form.contact_number', '917-12')->assertSet('form.representative_contact', '918-12')
            ->assertSet('form.first_name', 'SYNTHETIC')->assertSet('form.address', 'SYNTHETIC ADDRESS')
            ->assertSet('form.barangay_id', $input['barangay_id'])->assertSet('form.representative_name', 'SYNTHETIC REPRESENTATIVE')
            ->assertSet('form.representative_relationship', 'CHILD');

        $this->assertDatabaseEmpty('senior_citizens');
    }

    #[TestWith(['first_name', 'Test123'])]
    #[TestWith(['first_name', 'Test!'])]
    #[TestWith(['middle_name', 'Test2'])]
    #[TestWith(['middle_name', 'Test-Name'])]
    #[TestWith(['last_name', 'Test3'])]
    #[TestWith(['last_name', "Test'Name"])]
    #[TestWith(['name_suffix', '3'])]
    #[TestWith(['name_suffix', 'VI'])]
    public function test_personal_info_rejects_numbers_and_special_characters_in_names(string $field, string $value): void
    {
        $this->actingAs(User::factory()->create());
        $this->application()->set('form', [...$this->input(), $field => $value])->call('next')
            ->assertHasErrors('form.'.$field)->assertSet('step', 1);

        $this->assertDatabaseCount('senior_citizens', 0);
    }

    #[TestWith(['Jr.'])]
    #[TestWith(['Sr.'])]
    #[TestWith(['II'])]
    #[TestWith(['III'])]
    #[TestWith(['IV'])]
    #[TestWith(['V'])]
    public function test_personal_info_accepts_accented_letters_spaces_and_supported_suffixes(string $suffix): void
    {
        $this->actingAs(User::factory()->create());
        $this->application()->set('form', [...$this->input(), 'first_name' => 'Ñame Éxample',
            'middle_name' => "N\u{0303}ame", 'last_name' => 'De La Test', 'name_suffix' => $suffix])->call('next')
            ->assertHasNoErrors()->assertSet('step', 2);
    }

    public function test_registration_pages_require_authentication_including_in_production(): void
    {
        $this->app->instance('env', 'production');
        $this->get(route('registrations.index'))->assertRedirect(route('login'));
        $this->get(route('senior-citizens.create'))->assertRedirect(route('login'));
    }

    public function test_existing_create_page_and_queue_use_the_same_four_step_application(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('senior-citizens.create'))->assertOk()->assertSeeLivewire(IdApplication::class)->assertSee('New ID Application')->assertSee('Requirements');
        $this->get(route('registrations.index'))->assertOk()->assertSeeLivewire(Index::class)->assertDontSee('APP-26-1048')
            ->assertSeeLivewire(ApplicationLauncher::class)->assertSee('New ID Application')->assertDontSee('Import records');
        $this->get(route('senior-citizens.index'))->assertDontSeeLivewire(ApplicationLauncher::class)->assertDontSee('New ID Application')->assertSee('Import records');
        Livewire::test(ApplicationLauncher::class)->call('apply')->assertSet('applicationOpen', true)->assertSeeLivewire(IdApplication::class)
            ->dispatch('registration-cancelled')->assertSet('applicationOpen', false);
    }

    public function test_admin_can_monitor_registration_but_cannot_create_senior_records(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('senior-citizens.create'))->assertForbidden();
        $this->post(route('senior-citizens.store'), $this->input())->assertForbidden();
        $this->get(route('senior-citizens.index'))->assertOk()->assertDontSee('Register senior citizen');
        $this->get(route('registrations.index'))->assertOk()->assertDontSee('New ID Application');
        Livewire::test(ApplicationLauncher::class)->assertForbidden();
        Livewire::test(IdApplication::class)->assertForbidden();

        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_step_validation_keeps_values_when_moving_back_and_forward(): void
    {
        $this->actingAs(User::factory()->create());
        $input = $this->input();
        $this->application()->call('next')->assertSet('step', 1)->assertHasErrors(['form.first_name', 'form.birth_date'])
            ->set('form', $input)->call('next')->assertHasNoErrors()->assertSet('step', 2)
            ->set('form.address', '')->call('next')->assertSet('step', 2)->assertHasErrors('form.address')
            ->set('form.address', 'Synthetic address')->call('next')->assertSet('step', 3)->assertHasNoErrors()
            ->call('back')->assertSet('step', 2)->assertSet('form.first_name', 'SYNTHETIC')
            ->call('back')->assertSet('step', 1)->assertSet('form.address', 'SYNTHETIC ADDRESS');
        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_self_application_creates_one_verified_central_record_without_program_application(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $component = $this->application()->set('form', $this->input())->call('next')->call('next')
            ->call('submit')->assertHasErrors('informationReviewed')
            ->set('informationReviewed', true)->call('submit')->assertHasNoErrors();
        $senior = SeniorCitizen::sole();
        $component->assertRedirect(route('senior-id.show', [$senior, 'from' => 'registry']));
        $this->assertTrue(session('clear-registration-names'));
        $this->assertSame('VERIFIED', $senior->status->value);
        $this->assertNotNull($senior->verified_at);
        $this->assertNull($senior->id_generated_at);
        $this->assertNull($senior->osca_id_number);
        $this->assertSame('+639171234567', $senior->contact_number);
        $this->assertSame('SYNTHETIC', $senior->first_name);
        $this->assertSame('APPLICANT', $senior->last_name);
        $this->assertSame('SYNTHETIC ADDRESS', $senior->address);
        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('beneficiaries', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'senior_citizen.created', 'auditable_id' => $senior->id, 'user_id' => $staff->id]);
        $component->call('submit')->assertRedirect(route('senior-id.show', [$senior, 'from' => 'registry']));
        $this->assertDatabaseCount('senior_citizens', 1);

        $this->get(route('senior-id.show', [$senior, 'from' => 'registry']))
            ->assertOk()
            ->assertDontSee('New ID Application')
            ->assertSeeText('Back');
        $this->get(route('senior-citizens.create'))->assertOk()->assertSeeLivewire(IdApplication::class);
        $this->assertDatabaseCount('senior_citizens', 1);
    }

    #[TestWith([false, false])]
    #[TestWith([true, false])]
    #[TestWith([true, true])]
    public function test_save_actions_share_validation_and_save_once_without_issuing_an_id(bool $modal, bool $prepareId): void
    {
        $this->actingAs(User::factory()->create());
        $input = $this->input();
        $component = $this->withIdentityDocuments(Livewire::test(IdApplication::class, ['modal' => $modal]))
            ->set('form', $input)->call('next')->call('next')->call('next')->assertSet('step', 3);

        $component->call('submit', $prepareId)->assertHasErrors('informationReviewed')
            ->assertSet('form.first_name', 'SYNTHETIC')->assertSet('form.address', 'SYNTHETIC ADDRESS');
        $this->assertDatabaseEmpty('senior_citizens');
        $component->set('informationReviewed', true)->call('submit', $prepareId)->assertHasNoErrors();

        $senior = SeniorCitizen::sole();
        $destination = $prepareId ? route('senior-id.show', [$senior, 'from' => 'registry']) : route('registrations.index');
        $component->assertRedirect($destination)->call('submit', $prepareId)->assertRedirect($destination);
        $this->assertSame('Registration saved successfully.', session('status'));
        $this->assertSame('VERIFIED', $senior->status->value);
        $this->assertNotNull($senior->verified_at);
        $this->assertNull($senior->osca_id_number);
        $this->assertNull($senior->id_generated_at);
        $this->assertDatabaseCount('senior_citizens', 1);
        $this->assertDatabaseEmpty('senior_citizen_id_credentials');
        $this->assertDatabaseMissing('audit_logs', ['action' => 'senior_citizen.id_print_generated']);
        $this->get(route('registrations.index'))->assertSee($senior->last_name)->assertSee('Prepare ID');
    }

    public function test_saved_registrations_can_be_issued_and_printed_together_without_individual_preparation(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $input = $this->input();
        foreach (['First', 'Second'] as $lastName) {
            $this->application()->set('form', [...$input, 'last_name' => $lastName])
                ->call('next')->call('next')->set('informationReviewed', true)->call('submit', false)
                ->assertHasNoErrors()->assertRedirect(route('registrations.index'));
        }
        $seniors = SeniorCitizen::orderBy('id')->get();
        $this->assertCount(2, $seniors);
        foreach ($seniors as $senior) {
            $this->assertNull($senior->osca_id_number);
            $this->assertNull($senior->id_generated_at);
        }
        app(IdOfficials::class)->save(
            ['osca_head_name' => 'Synthetic Head', 'municipal_mayor_name' => 'Synthetic Mayor'],
            ['municipal_seal' => UploadedFile::fake()->createWithContent('synthetic.jpg', file_get_contents(base_path('tests/Fixtures/id-photo.jpg')))]
        );

        $this->post(route('senior-id.batch-print'), ['senior_ids' => $seniors->modelKeys(), 'paper' => 'a4', 'purpose' => 'FIRST_PRINT', 'reviewed' => true])
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        foreach ($seniors as $senior) {
            $senior->refresh();
            $this->assertSame('VERIFIED', $senior->status->value);
            $this->assertNotNull($senior->id_generated_at);
            $this->assertNotNull($senior->osca_id_number);
            $this->assertSame(1, $senior->idPrints()->count());
        }
        $this->assertDatabaseCount('senior_citizens', 2);
        $this->assertDatabaseCount('senior_citizen_id_credentials', 2);
        $this->assertNotSame($seniors[0]->osca_id_number, $seniors[1]->osca_id_number);
    }

    public function test_representative_details_are_checked_before_document_step_and_optional_authorization_is_stored(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $component = $this->application()->set('form', [...$this->input(), 'requirements_checks' => ['identity' => true, 'residency' => true, 'authorization' => true], 'representative_name' => 'Synthetic Representative'])
            ->call('next')->call('next')->assertSet('step', 2)->assertHasErrors(['form.representative_relationship', 'form.representative_contact'])
            ->set('form.representative_name', 'Synthetic Representative')->set('form.representative_relationship', 'Child')->set('form.representative_contact', '917-123-4567')
            ->call('next')->assertSet('step', 3)->assertHasNoErrors()
            ->assertDontSee('Proof of authorization')->set('informationReviewed', true)
            ->set('authorizationFile', UploadedFile::fake()->createWithContent('proof.pdf', "%PDF-1.4\nSynthetic proof"))
            ->call('submit')->assertHasNoErrors();
        $senior = SeniorCitizen::sole();
        $component->assertRedirect(route('senior-id.show', [$senior, 'from' => 'registry']));
        $this->assertSame('+639171234567', $senior->representative_contact);
        $this->assertSame('SYNTHETIC REPRESENTATIVE', $senior->representative_name);
        $this->assertSame('CHILD', $senior->representative_relationship);
        $this->assertSame('REPRESENTATIVE', $senior->applicant_type);
        $document = $senior->documents()->where('document_type', 'AUTHORIZATION')->sole();
        Storage::disk('local')->assertExists($document->path);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_representative_application_saves_without_authorization_upload(bool $modal): void
    {
        $this->actingAs(User::factory()->create());
        $component = $this->withIdentityDocuments(Livewire::test(IdApplication::class, ['modal' => $modal]))
            ->set('form', [...$this->input(), 'requirements_checks' => ['identity' => true, 'residency' => true, 'authorization' => true],
                'representative_name' => 'Synthetic Representative', 'representative_relationship' => 'Child', 'representative_contact' => '917-000-0002'])
            ->call('next')->call('next')->call('next')->assertSet('step', 3)
            ->assertSee('Authorized Representative Information')->assertSee('SYNTHETIC REPRESENTATIVE')->assertSee('CHILD')
            ->assertSee('Authorization Letter & Representative ID')->assertSee('Checked')
            ->assertDontSee('Proof of authorization')->assertDontSeeHtml('type="file"');

        $component->call('submit')->assertHasErrors('informationReviewed');
        $this->assertDatabaseEmpty('senior_citizens');
        $component->set('informationReviewed', true)->call('submit')->assertHasNoErrors();

        $senior = SeniorCitizen::sole();
        $component->assertRedirect(route('senior-id.show', [$senior, 'from' => 'registry']));
        $this->assertSame('REPRESENTATIVE', $senior->applicant_type);
        $this->assertSame('SYNTHETIC REPRESENTATIVE', $senior->representative_name);
        $this->assertSame('CHILD', $senior->representative_relationship);
        $this->assertSame('+639170000002', $senior->representative_contact);
        $this->assertTrue($senior->requirements_checks['authorization']);
        $this->assertSame('VERIFIED', $senior->status->value);
        $this->assertFalse($senior->documents()->where('document_type', 'AUTHORIZATION')->exists());
    }

    public function test_identification_uploads_are_private_and_unexpected_authoritative_fields_are_ignored(): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public']);
        $this->actingAs(User::factory()->create());
        $input = $this->input();
        $this->application()->set('form', [...$input, 'status' => 'VERIFIED', 'verified_at' => now()->toDateTimeString(), 'id_generated_at' => now()->toDateTimeString()])
            ->call('next')->call('next')
            ->set('informationReviewed', true)->call('submit')->assertHasNoErrors();

        $senior = SeniorCitizen::sole();
        $this->assertSame('VERIFIED', $senior->status->value);
        $this->assertNull($senior->id_generated_at);
        $this->assertSame(2, $senior->documents()->count());
        $id = $senior->documents()->where('document_type', 'IDENTIFICATION')->sole();
        $birthCertificate = $senior->documents()->where('document_type', 'BIRTH_CERTIFICATE')->sole();
        $this->assertSame($input['id_type_id'], $id->id_type_id);
        $this->assertNull($birthCertificate->id_type_id);
        foreach ($senior->documents()->get() as $document) {
            $this->assertSame('local', $document->storage_disk);
            $stored = Storage::disk('local')->get($document->path);
            $this->assertSame(strlen($stored), $document->stored_size);
            $this->assertLessThan($document->size, $document->stored_size);
            Storage::disk('public')->assertMissing($document->path);
            $preview = $this->get(route('senior-citizens.document', [$senior, $document]))
                ->assertOk()->assertHeader('Content-Disposition', 'inline; filename=supporting-document.pdf')
                ->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringContainsString('no-store', $preview->headers->get('Cache-Control'));
            $this->assertSame($this->documentContents(), $preview->streamedContent());
        }
    }

    public function test_invalid_and_oversized_documents_are_rejected_without_creating_records(): void
    {
        $this->actingAs(User::factory()->create());
        $this->withIdentityDocuments(Livewire::test(IdApplication::class))->set('form', $this->input())
            ->set('identificationFile', UploadedFile::fake()->createWithContent('large.pdf', "%PDF-1.4\n".str_repeat('x', 10240 * 1024)))
            ->call('next')->assertHasErrors('identificationFile')->assertSet('step', 0)
            ->set('identificationFile', UploadedFile::fake()->createWithContent('script.html', '<script>alert(1)</script>'))
            ->call('next')->assertHasErrors('identificationFile')->assertSet('step', 0);
        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_document_checks_alone_cannot_replace_both_uploads_and_an_id_type(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(IdApplication::class)
            ->set('form.requirements_checks.identity', true)->set('form.requirements_checks.residency', true)
            ->call('next')->assertSet('step', 0)
            ->assertHasErrors(['birthCertificateFile', 'identificationFile', 'form.id_type_id']);
    }

    public function test_the_wizard_loads_active_id_types_and_rejects_unavailable_selections(): void
    {
        $this->actingAs(User::factory()->create());
        $inactive = $this->nationalId();
        $inactive->update(['is_active' => false]);
        $unavailable = IdType::query()->where('code', 'no_valid_id')->sole();

        $this->withIdentityDocuments(Livewire::test(IdApplication::class))
            ->assertSee('Philippine Passport')->assertSee('No Valid ID Available')->assertDontSee('National ID (PhilSys)')
            ->set('form.requirements_checks.identity', true)->set('form.requirements_checks.residency', true)
            ->set('form.id_type_id', $inactive->id)->call('next')->assertHasErrors('form.id_type_id')->assertSet('step', 0)
            ->set('form.id_type_id', $unavailable->id)->call('next')->assertHasErrors('form.id_type_id')->assertSet('step', 0);
    }

    public function test_submission_revalidates_identity_and_returns_to_the_step_with_errors(): void
    {
        $this->actingAs(User::factory()->create());
        $input = $this->input();
        $component = $this->application()->set('form', $input)->call('next')->call('next');
        SeniorCitizen::create([...Arr::except($input, ['requirements_status', 'id_type_id']), 'status' => 'DECEASED']);
        $component->set('informationReviewed', true)->call('submit')->assertHasErrors('form.first_name')->assertSet('step', 1);
        $this->assertDatabaseCount('senior_citizens', 1);
    }

    public function test_queue_filters_database_stages_barangays_and_escapes_names(): void
    {
        $this->actingAs(User::factory()->create());
        $input = $this->input();
        $pending = SeniorCitizen::create([...Arr::except($input, ['requirements_status', 'id_type_id']), 'first_name' => '<script>synthetic</script>']);
        $verified = SeniorCitizen::create([...Arr::except($input, ['requirements_status', 'id_type_id']), 'first_name' => '<script>synthetic</script>', 'last_name' => 'Verified', 'status' => 'VERIFIED']);
        $issued = SeniorCitizen::create([...Arr::except($input, ['requirements_status', 'id_type_id']), 'last_name' => 'Issued', 'status' => 'VERIFIED']);
        $issued->forceFill(['id_generated_at' => now()])->save();
        Livewire::test(Index::class)->assertDontSee($pending->full_name)->assertSee($verified->full_name)->assertDontSee('<script>synthetic</script>', false)
            ->set('stage', 'VERIFIED')->assertSee($verified->full_name)->assertDontSee($pending->full_name)->assertDontSee($issued->full_name)
            ->set('stage', 'ISSUED')->assertSee($issued->full_name)->assertDontSee($verified->full_name)
            ->set('barangayId', '999999')->assertSee('No matching registration records');
    }

    public function test_inactive_user_cannot_mount_or_write_registration(): void
    {
        $this->actingAs(User::factory()->inactive()->create());
        $this->get(route('registrations.index'))->assertRedirect(route('login'));
        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_hiding_optional_representative_preserves_their_unsaved_details(): void
    {
        $this->actingAs(User::factory()->create());
        $this->application()->set('form', $this->input())->set('form.requirements_checks.authorization', true)
            ->set('form.representative_name', 'Synthetic Representative')->set('form.representative_contact', '09171234567')
            ->set('form.requirements_checks.authorization', false)->assertSet('form.representative_name', 'Synthetic Representative')->assertSet('form.representative_contact', '09171234567')
            ->set('form.requirements_checks.authorization', true)->assertSet('form.representative_name', 'Synthetic Representative');
    }

    public function test_launcher_starts_each_new_application_with_a_distinct_component_session(): void
    {
        $this->actingAs(User::factory()->create());
        session()->flash('status', 'Previous registration saved.');
        $launcher = Livewire::test(ApplicationLauncher::class)->call('apply')
            ->assertSet('applicationOpen', true)->assertSet('applicationSession', 1)
            ->assertSeeLivewire(IdApplication::class)->assertDontSee('restoreNames')->assertDontSee('sessionStorage.getItem', false);
        $this->assertFalse(session()->has('status'));

        $launcher->call('apply')->assertSet('applicationSession', 1)
            ->dispatch('registration-cancelled')->assertSet('applicationOpen', false)
            ->call('apply')->assertSet('applicationOpen', true)->assertSet('applicationSession', 2);

        $this->assertDatabaseEmpty('senior_citizens');
    }

    public function test_new_application_after_cancellation_has_no_previous_values_files_errors_or_completed_steps(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $this->application()->set('form', [...$this->input(),
            'requirements_checks' => ['identity' => true, 'residency' => true, 'authorization' => true],
            'representative_name' => 'Synthetic Representative', 'representative_relationship' => 'Child', 'representative_contact' => '917-000-0002',
        ])->call('next')->call('next')
            ->set('identificationFile', UploadedFile::fake()->createWithContent('previous.pdf', "%PDF-1.4\nSynthetic prior upload"))
            ->set('authorizationFile', UploadedFile::fake()->createWithContent('previous-authorization.pdf', "%PDF-1.4\nSynthetic prior proof"))
            ->call('submit')->assertHasErrors('informationReviewed')->call('cancel')->assertDispatched('registration-cancelled');

        $this->assertFreshApplication(Livewire::test(IdApplication::class, ['modal' => true]));
        $this->assertDatabaseEmpty('senior_citizens');
    }

    public function test_new_application_after_saving_starts_blank_and_keeps_the_saved_record(): void
    {
        $this->actingAs(User::factory()->create());
        $this->application()->set('form', $this->input())->call('next')->call('next')
            ->set('informationReviewed', true)->call('submit')->assertHasNoErrors();
        $saved = SeniorCitizen::sole();
        $original = $saved->getAttributes();

        $this->assertFreshApplication(Livewire::test(IdApplication::class, ['modal' => true]));

        $this->assertSame($original, $saved->fresh()->getAttributes());
        $this->assertDatabaseCount('senior_citizens', 1);
    }

    private function assertFreshApplication(Testable $component): void
    {
        $component->assertSet('step', 0)->assertSet('completedSteps', [])->assertSet('registeredSeniorId', null)
            ->assertSet('informationReviewed', false)->assertSet('authorizationFile', null)->assertSet('identificationFile', null)->assertSet('birthCertificateFile', null)
            ->assertSet('form.requirements_checks', ['identity' => false, 'residency' => false, 'authorization' => false])
            ->assertSet('form.contact_number', '9')->assertSet('form.representative_contact', '9')->assertHasNoErrors()
            ->assertDontSee('Authorized Representative Information');
        foreach (['id_type_id', 'first_name', 'last_name', 'middle_name', 'name_suffix', 'birth_date', 'sex', 'civil_status', 'barangay_id', 'address', 'representative_name', 'representative_relationship'] as $field) {
            $component->assertSet('form.'.$field, '');
        }
        $component->call('goToStep', 3)->assertSet('step', 0);
    }

    public function test_authorization_check_is_the_only_trigger_for_required_representative_fields_and_summary(): void
    {
        $this->actingAs(User::factory()->create());
        $input = $this->input();
        $component = $this->application()->set('form', $input)
            ->set('form.requirements_checks.authorization', true)->call('next')
            ->assertSet('step', 2)->assertSee('Authorized Representative Information')
            ->assertSee('Representative full name')->assertSee('Representative mobile number')
            ->assertDontSee('Add an authorized representative for SMS or future claiming')
            ->assertDontSee('Applicant type')->call('next')->assertSet('step', 2)
            ->assertHasErrors(['form.representative_name', 'form.representative_relationship', 'form.representative_contact']);

        $component->set('form.representative_name', 'Synthetic Representative')->set('form.representative_relationship', 'Child')
            ->set('form.representative_contact', '917-000-0002')->call('next')->assertSet('step', 3)->assertHasNoErrors()
            ->assertSee('Authorized Representative Information')->assertSee('SYNTHETIC REPRESENTATIVE')->assertSee('CHILD');
        $this->assertDatabaseEmpty('senior_citizens');
    }

    public function test_unchecked_authorization_excludes_hidden_representative_fields_and_proof_from_saving(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $component = $this->application()->set('form', [...$this->input(), 'applicant_type' => 'REPRESENTATIVE',
            'representative_name' => 'Stale Representative', 'representative_relationship' => 'Child', 'representative_contact' => 'invalid'])
            ->call('next')->assertSet('step', 2)->assertDontSee('Authorized Representative Information')
            ->call('next')->assertSet('step', 3)->assertDontSee('Authorized Representative Information')
            ->assertDontSee('Proof of authorization')->assertHasNoErrors();

        $component->set('authorizationFile', UploadedFile::fake()->createWithContent('stale.pdf', "%PDF-1.4\nSynthetic stale proof"))
            ->set('informationReviewed', true)->call('submit')->assertHasNoErrors();

        $senior = SeniorCitizen::sole();
        $this->assertSame('SELF', $senior->applicant_type);
        $this->assertNull($senior->representative_name);
        $this->assertNull($senior->representative_relationship);
        $this->assertNull($senior->representative_contact);
        $this->assertFalse($senior->documents()->where('document_type', 'AUTHORIZATION')->exists());
    }

    public function test_step_navigation_only_allows_validated_steps_and_preserves_values(): void
    {
        $this->actingAs(User::factory()->create());
        $input = $this->input();
        $component = $this->withIdentityDocuments(Livewire::test(IdApplication::class)->call('goToStep', 3)->assertSet('step', 0))
            ->set('form', $input)->call('next')->assertSet('completedSteps.0', true)
            ->call('next')->assertSet('completedSteps.1', true)->assertSet('step', 2)
            ->call('goToStep', 3)->assertSet('step', 2)
            ->call('next')->assertSet('step', 3)->assertSet('completedSteps.2', true)
            ->call('goToStep', 0)->assertSet('step', 0)->assertSet('form.address', 'SYNTHETIC ADDRESS')
            ->call('goToStep', 2)->assertSet('step', 2)->assertSet('form.first_name', 'SYNTHETIC');

        $component->set('form.address', '')->assertSet('completedSteps', [0 => true, 1 => true])
            ->call('goToStep', 3)->assertSet('step', 2)->call('next')->assertHasErrors('form.address');
        $this->assertDatabaseEmpty('senior_citizens');
    }

    public function test_authorization_changes_invalidate_step_completion_and_require_revalidation(): void
    {
        $this->actingAs(User::factory()->create());
        $component = $this->withIdentityDocuments(Livewire::test(IdApplication::class))->set('form', $this->input())
            ->call('next')->call('next')->call('next')->assertSet('step', 3)
            ->set('informationReviewed', true)->call('goToStep', 0)->assertSet('informationReviewed', false)
            ->set('form.requirements_checks.authorization', true)->assertSet('completedSteps', [])
            ->call('goToStep', 3)->assertSet('step', 0)
            ->call('next')->call('next')->assertSet('step', 2)->assertSee('Authorized Representative Information')
            ->call('next')->assertSet('step', 2)->assertHasErrors('form.representative_name')
            ->call('goToStep', 0)->set('form.requirements_checks.authorization', false)
            ->call('next')->call('next')->assertDontSee('Authorized Representative Information')
            ->call('next')->assertSet('step', 3)->assertHasNoErrors();

        $component->assertDontSee('Proof of authorization');
        $this->assertDatabaseEmpty('senior_citizens');
    }

    #[TestWith(['ARCHIVED'])]
    #[TestWith(['DECEASED'])]
    #[TestWith(['TRANSFERRED'])]
    public function test_registrations_retain_existing_inactive_records_but_exclude_applications(string $status): void
    {
        $this->actingAs(User::factory()->create());
        $input = Arr::except($this->input(), ['requirements_status', 'id_type_id']);
        $pending = SeniorCitizen::create([...$input, 'last_name' => 'Pending']);
        $existing = SeniorCitizen::create([...$input, 'last_name' => 'Existing', 'status' => $status]);

        Livewire::test(Index::class)->assertSee($existing->full_name)->assertDontSee($pending->full_name)
            ->assertSee('New ID Application')->assertDontSeeLivewire(IdApplication::class)
            ->set('stage', 'INACTIVE')->assertSee($existing->full_name)
            ->set('search', 'Pending')->assertDontSee($pending->full_name);
    }

    public function test_registry_application_cancel_preserves_the_list_filters(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::withQueryParams(['status' => 'PENDING', 'search' => 'Synthetic', 'page' => 2])
            ->test(IdApplication::class)->call('cancel')
            ->assertRedirect(route('senior-citizens.index', ['search' => 'Synthetic', 'status' => 'PENDING', 'page' => 2]));
    }

    #[TestWith(['registry', 'senior-citizens.index'])]
    #[TestWith(['registrations', 'registrations.index'])]
    public function test_id_navigation_keeps_the_origin_and_filters(string $from, string $route): void
    {
        $this->actingAs(User::factory()->create());
        $senior = SeniorCitizen::create([...Arr::except($this->input(), ['requirements_status', 'id_type_id'])]);
        $context = ['search' => 'Synthetic', 'page' => 2];

        $response = $this->get(route('senior-id.show', [$senior, 'from' => $from, ...$context]));

        $response->assertOk()
            ->assertDontSee('Back to senior record')
            ->assertDontSee('Back to registrations')
            ->assertDontSee('Exit to registrations')
            ->assertDontSee('Exit to registry');
        $document = new \DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $backLinks = $xpath->query('//header//a[contains(normalize-space(.), "Back")]');
        $this->assertCount(1, $backLinks);
        $this->assertSame(route($route, $context), $backLinks->item(0)->getAttribute('href'));
        $links = $xpath->query('//nav[@aria-label="OSCA Staff navigation"]//a[@aria-current="page"]');
        $this->assertCount(1, $links);
        $this->assertSame(route($route), $links->item(0)->getAttribute('href'));
        $this->assertCount(0, $xpath->query('//nav[@aria-label="Records navigation"]'));
    }

    public function test_birth_date_changes_detect_age_and_clear_the_underage_error(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 12)->startOfDay());
        $this->actingAs(User::factory()->create());

        $this->application()
            ->set('form.birth_date', '1966-10-13')->assertSee('Age: 59 years.')->assertHasErrors('form.birth_date')
            ->set('form.birth_date', '1966-10-12')->assertSee('Age: 60 years.')->assertSee('Meets the 60-year age requirement.')->assertHasNoErrors();
    }

    public function test_record_links_and_profile_edits_preserve_registration_navigation(): void
    {
        $this->actingAs(User::factory()->create());
        $input = $this->input();
        $senior = SeniorCitizen::create([...Arr::except($input, ['requirements_status', 'id_type_id']), 'status' => 'VERIFIED']);
        $context = ['from' => 'registrations', 'search' => 'Synthetic', 'stage' => 'VERIFIED', 'page' => 2];

        $this->get(route('senior-citizens.show', [$senior, ...$context]))
            ->assertSeeHtml('href="'.e(route('senior-citizens.edit', [$senior, ...$context])).'"')
            ->assertSeeHtml('href="'.e(route('senior-id.show', [$senior, ...$context])).'"');
        $this->get(route('senior-citizens.edit', [$senior, ...$context]))
            ->assertSeeHtml('href="'.e(route('senior-citizens.show', [$senior, ...$context])).'"');
        $this->put(route('senior-citizens.update', [$senior, ...$context]), [...$input, 'address' => 'Updated synthetic address'])
            ->assertSessionHasNoErrors()->assertRedirect(route('senior-citizens.show', [$senior, ...$context]));

        $this->assertSame('UPDATED SYNTHETIC ADDRESS', $senior->fresh()->address);
    }

    #[TestWith(['1966-10-12'])]
    #[TestWith(['1956-10-12'])]
    public function test_applicants_can_apply_on_their_sixtieth_birthday_and_later(string $birthDate): void
    {
        $this->travelTo(now()->setDate(2026, 10, 12)->startOfDay());
        $this->actingAs(User::factory()->create());

        $this->application()->set('form', [...$this->input(), 'birth_date' => $birthDate])
            ->call('next')->assertSet('step', 2)->call('next')
            ->set('informationReviewed', true)->call('submit')->assertHasNoErrors();

        $this->assertDatabaseHas('senior_citizens', ['birth_date' => $birthDate, 'status' => 'VERIFIED']);
    }

    public function test_submission_rechecks_age_when_the_birth_date_is_changed_after_the_first_step(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 12)->startOfDay());
        $this->actingAs(User::factory()->create());
        $input = $this->input();

        $this->application()->set('form', $input)->call('next')->call('next')
            ->set('form', [...$input, 'birth_date' => '1966-10-13'])->set('informationReviewed', true)
            ->call('submit')->assertHasErrors('form.birth_date')->assertSet('step', 1);

        $this->assertDatabaseCount('senior_citizens', 0);
    }

    #[TestWith(['2026-10-13'])]
    #[TestWith(['1960-02-30'])]
    #[TestWith(['invalid'])]
    #[TestWith([''])]
    public function test_invalid_or_future_birth_dates_do_not_show_an_age_or_advance(string $birthDate): void
    {
        $this->travelTo(now()->setDate(2026, 10, 12)->startOfDay());
        $this->actingAs(User::factory()->create());

        $this->application()->set('form', [...$this->input(), 'birth_date' => $birthDate])
            ->assertDontSee('Age:')->call('next')->assertHasErrors('form.birth_date')->assertSet('step', 1);

        $this->assertDatabaseCount('senior_citizens', 0);
    }

    #[TestWith(['SINGLE'])]
    #[TestWith(['MARRIED'])]
    #[TestWith(['WIDOWED'])]
    public function test_civil_status_choices_are_saved(string $status): void
    {
        $this->actingAs(User::factory()->create());
        $this->application()->set('form', [...$this->input(), 'civil_status' => $status])
            ->call('next')->call('next')->set('informationReviewed', true)->call('submit')->assertHasNoErrors();
        $this->assertSame($status, SeniorCitizen::sole()->civil_status);
    }

    public function test_unlisted_civil_status_is_rejected_during_application_and_profile_update(): void
    {
        $this->actingAs(User::factory()->create());
        $input = $this->input();
        $this->application()->set('form', [...$input, 'civil_status' => 'INVALID'])->call('next')->assertSet('step', 1)->assertHasErrors('form.civil_status');
        $senior = SeniorCitizen::create([...Arr::except($input, ['requirements_status', 'id_type_id']), 'civil_status' => 'SINGLE']);
        $this->put(route('senior-citizens.update', $senior), [...$input, 'civil_status' => 'INVALID'])->assertSessionHasErrors('civil_status');
        $this->assertSame('SINGLE', $senior->fresh()->civil_status);
    }

    #[TestWith(['9672348976'])]
    #[TestWith(['967-234-8976'])]
    #[TestWith(['0967 234 8976'])]
    #[TestWith(['+63 967-234-8976'])]
    #[TestWith(['639672348976'])]
    public function test_registration_saves_uppercase_unicode_text_and_canonical_mobile_numbers(string $number): void
    {
        $this->actingAs(User::factory()->create());
        $this->application()->set('form', [
            ...$this->input(), 'first_name' => '  synthetic ñandú ', 'middle_name' => 'mixed',
            'last_name' => 'de synthetic', 'name_suffix' => 'jr', 'address' => '  synthetic street ', 'contact_number' => $number,
        ])->call('next')->call('next')->assertSet('step', 3)->assertHasNoErrors()
            ->set('informationReviewed', true)->call('submit')->assertHasNoErrors();

        $senior = SeniorCitizen::sole();
        $this->assertSame('SYNTHETIC ÑANDÚ', $senior->first_name);
        $this->assertSame('MIXED', $senior->middle_name);
        $this->assertSame('DE SYNTHETIC', $senior->last_name);
        $this->assertSame('Jr.', $senior->name_suffix);
        $this->assertSame('SYNTHETIC STREET', $senior->address);
        $this->assertSame('+639672348976', $senior->contact_number);
    }

    #[TestWith(['867-234-8976'])]
    #[TestWith(['967-234-897'])]
    #[TestWith(['967-234-89760'])]
    #[TestWith(['+1 967-234-8976'])]
    #[TestWith(['967abc2348976'])]
    #[TestWith(['+63+639672348976'])]
    #[TestWith([['9672348976']])]
    public function test_both_registration_entry_points_reject_invalid_senior_and_representative_numbers(mixed $number): void
    {
        $this->actingAs(User::factory()->create());
        $input = [...$this->input(), 'contact_number' => $number, 'requirements_checks' => ['identity' => true, 'residency' => true, 'authorization' => true], 'applicant_type' => 'REPRESENTATIVE',
            'representative_name' => 'Synthetic Representative', 'representative_relationship' => 'Child', 'representative_contact' => $number];

        $this->application()->set('form', $input)->call('next')->call('next')->assertSet('step', 2)
            ->assertHasErrors(['form.contact_number', 'form.representative_contact']);
        $this->post(route('senior-citizens.store'), $input)->assertSessionHasErrors(['contact_number', 'representative_contact']);

        $this->assertDatabaseCount('senior_citizens', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_document_checks_are_required_without_an_assessment_selection(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(IdApplication::class)->assertDontSee('Document assessment')
            ->assertDontSee('Who is applying?')
            ->assertSee('Birth Certificate / Proof of Age')->assertSee('Valid Government-Issued ID')
            ->call('next')->assertSet('step', 0)
            ->assertHasErrors(['form.requirements_checks.identity', 'form.requirements_checks.residency'])
            ->assertDontSee('Please check all required document requirements before continuing.')
            ->assertSee('data-error-display="outline" aria-invalid="true"', false)
            ->assertDontSee('The requirements checks.identity field must be accepted.')
            ->set('form.requirements_checks.identity', true)->set('form.requirements_checks.residency', true);

        $this->withIdentityDocuments($component)->set('form.id_type_id', $this->nationalId()->id)
            ->call('next')->assertHasNoErrors()->assertSet('step', 1)
            ->assertDontSee('Fields marked', false)
            ->assertSee('class="field-label-text">First name<!--[if BLOCK]><![endif]--><span class="field-required" aria-hidden="true">*</span>', false)
            ->assertSee('class="field-label-text">Middle name<!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]--></span>', false);
    }

    #[TestWith(['MALE', true])]
    #[TestWith(['FEMALE', true])]
    #[TestWith(['OTHER', false])]
    #[TestWith(['', false])]
    public function test_registration_accepts_only_male_or_female(string $sex, bool $valid): void
    {
        $this->actingAs(User::factory()->create());
        $input = [...$this->input(), 'sex' => $sex];
        $component = $this->application()->assertDontSee('value="OTHER"', false)->set('form', $input)->call('next');

        if ($valid) {
            $component->assertHasNoErrors()->assertSet('step', 2);
        } else {
            $component->assertHasErrors('form.sex')->assertSet('step', 1);
            $this->post(route('senior-citizens.store'), $input)->assertSessionHasErrors('sex');
        }
        $this->assertDatabaseCount('senior_citizens', 0);
    }

    private function application(bool $modal = false): Testable
    {
        return $this->withIdentityDocuments(Livewire::test(IdApplication::class, ['modal' => $modal]))
            ->set('form.id_type_id', $this->nationalId()->id)
            ->set('form.requirements_checks.identity', true)
            ->set('form.requirements_checks.residency', true)
            ->call('next')->assertHasNoErrors()->assertSet('step', 1);
    }

    private function withIdentityDocuments(Testable $component): Testable
    {
        Storage::fake('local');
        config(['uploads.senior_document_disk' => 'local']);

        return $component
            ->set('birthCertificateFile', UploadedFile::fake()->createWithContent('birth-certificate.pdf', $this->documentContents()))
            ->set('identificationFile', UploadedFile::fake()->createWithContent('government-id.pdf', $this->documentContents()));
    }

    private function documentContents(): string
    {
        return "%PDF-1.4\n".str_repeat('Synthetic PDF content. ', 30);
    }

    private function nationalId(): IdType
    {
        return IdType::query()->where('code', 'national_id')->sole();
    }

    public function test_registration_rejects_a_digital_senior_photo(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());

        $this->post(route('senior-citizens.store'), [...$this->input(), 'photo' => UploadedFile::fake()->image('synthetic.png')])
            ->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('senior_citizens', 0);
        $this->assertDatabaseCount('senior_citizen_documents', 0);
    }

    private function input(): array
    {
        $barangay = Barangay::create(['name' => 'Synthetic Barangay', 'code' => 'SYN']);

        return ['id_type_id' => $this->nationalId()->id, 'requirements_status' => 'CHECKED', 'requirements_checks' => ['identity' => true, 'residency' => true, 'authorization' => false], 'review_reason' => '', 'first_name' => 'Synthetic', 'middle_name' => '', 'last_name' => 'Applicant', 'name_suffix' => null, 'birth_date' => '1950-01-01', 'sex' => 'FEMALE', 'civil_status' => '', 'barangay_id' => $barangay->id, 'address' => 'Synthetic address', 'contact_number' => '09171234567', 'applicant_type' => 'SELF', 'representative_name' => '', 'representative_relationship' => '', 'representative_contact' => ''];
    }
}
