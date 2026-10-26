<?php

namespace Tests\Feature\SeniorCitizens;

use App\Livewire\Registrations\IdApplication;
use App\Livewire\Registrations\Index;
use App\Livewire\SeniorCitizens\ApplicationLauncher;
use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\User;
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
        $this->get(route('registrations.index'))->assertOk()->assertSeeLivewire(Index::class)->assertDontSee('APP-26-1048');
        $this->get(route('senior-citizens.index'))->assertSeeLivewire(ApplicationLauncher::class)->assertSee('New ID Application');
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

    public function test_self_application_creates_one_pending_central_record_without_program_application(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $component = $this->application()->set('form', $this->input())->call('next')->call('next')
            ->call('submit')->assertHasErrors('informationReviewed')
            ->set('informationReviewed', true)->call('submit')->assertHasNoErrors();
        $senior = SeniorCitizen::sole();
        $component->assertRedirect(route('senior-id.show', [$senior, 'from' => 'registry']));
        $this->assertTrue(session('clear-registration-names'));
        $this->assertSame('PENDING', $senior->status->value);
        $this->assertNull($senior->verified_at);
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
            ->assertSeeHtml('href="'.route('senior-citizens.create').'"')
            ->assertSee('New ID Application')
            ->assertSee('Exit to registry');
        $this->get(route('senior-citizens.create'))->assertOk()->assertSeeLivewire(IdApplication::class);
        $this->assertDatabaseCount('senior_citizens', 1);
    }

    public function test_representative_details_are_checked_before_document_step_and_authorization_is_required(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $component = $this->application()->set('form', [...$this->input(), 'applicant_type' => 'REPRESENTATIVE'])
            ->call('next')->call('next')->assertSet('step', 2)->assertHasErrors(['form.representative_name', 'form.representative_contact'])
            ->set('form.representative_name', 'Synthetic Representative')->set('form.representative_relationship', 'Child')->set('form.representative_contact', '917-123-4567')
            ->call('next')->assertSet('step', 3)->assertHasNoErrors()
            ->set('informationReviewed', true)->call('submit')->assertHasErrors('authorizationFile')
            ->set('authorizationFile', UploadedFile::fake()->createWithContent('proof.pdf', "%PDF-1.4\nSynthetic proof"))
            ->call('submit')->assertHasNoErrors();
        $senior = SeniorCitizen::sole();
        $component->assertRedirect(route('senior-id.show', [$senior, 'from' => 'registry']));
        $this->assertSame('+639171234567', $senior->representative_contact);
        $this->assertSame('SYNTHETIC REPRESENTATIVE', $senior->representative_name);
        $this->assertSame('CHILD', $senior->representative_relationship);
        $document = $senior->documents()->sole();
        $this->assertSame('AUTHORIZATION', $document->document_type);
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_identification_uploads_are_private_and_unexpected_authoritative_fields_are_ignored(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        config(['filesystems.default' => 'public']);
        $this->actingAs(User::factory()->create());
        $this->application()->set('form', [...$this->input(), 'status' => 'VERIFIED', 'verified_at' => now()->toDateTimeString(), 'id_generated_at' => now()->toDateTimeString()])
            ->call('next')->call('next')->set('identificationFile', UploadedFile::fake()->createWithContent('id.pdf', "%PDF-1.4\nSynthetic identification"))
            ->set('informationReviewed', true)->call('submit')->assertHasNoErrors();
        $senior = SeniorCitizen::sole();
        $this->assertSame('PENDING', $senior->status->value);
        $this->assertNull($senior->id_generated_at);
        $this->assertSame(1, $senior->documents()->count());
        foreach ($senior->documents()->get() as $document) {
            Storage::disk('local')->assertExists($document->path);
            Storage::disk('public')->assertMissing($document->path);
        }
    }

    public function test_invalid_and_oversized_documents_are_rejected_without_creating_records(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $this->application()->set('form', $this->input())->call('next')->call('next')
            ->set('identificationFile', UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf'))
            ->set('informationReviewed', true)->call('submit')->assertHasErrors('identificationFile')
            ->set('identificationFile', UploadedFile::fake()->createWithContent('script.html', '<script>alert(1)</script>'))->call('submit')->assertHasErrors('identificationFile');
        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_submission_revalidates_identity_and_returns_to_the_step_with_errors(): void
    {
        $this->actingAs(User::factory()->create());
        $input = $this->input();
        $component = $this->application()->set('form', $input)->call('next')->call('next');
        SeniorCitizen::create([...Arr::except($input, ['requirements_status']), 'registration_number' => 'SYNTHETIC-EXISTING', 'status' => 'DECEASED']);
        $component->set('informationReviewed', true)->call('submit')->assertHasErrors('form.first_name')->assertSet('step', 1);
        $this->assertDatabaseCount('senior_citizens', 1);
    }

    public function test_queue_filters_database_stages_barangays_and_escapes_names(): void
    {
        $this->actingAs(User::factory()->create());
        $input = $this->input();
        $pending = SeniorCitizen::create([...Arr::except($input, ['requirements_status']), 'first_name' => '<script>synthetic</script>', 'registration_number' => 'SYNTHETIC-PENDING']);
        $verified = SeniorCitizen::create([...Arr::except($input, ['requirements_status']), 'first_name' => '<script>synthetic</script>', 'registration_number' => 'SYNTHETIC-VERIFIED', 'status' => 'VERIFIED']);
        $issued = SeniorCitizen::create([...Arr::except($input, ['requirements_status']), 'registration_number' => 'SYNTHETIC-ISSUED', 'status' => 'VERIFIED']);
        $issued->forceFill(['id_generated_at' => now()])->save();
        Livewire::test(Index::class)->assertDontSee($pending->registration_number)->assertSee($verified->registration_number)->assertDontSee('<script>synthetic</script>', false)
            ->set('stage', 'VERIFIED')->assertSee($verified->registration_number)->assertDontSee($pending->registration_number)->assertDontSee($issued->registration_number)
            ->set('stage', 'ISSUED')->assertSee($issued->registration_number)->assertDontSee($verified->registration_number)
            ->set('barangayId', '999999')->assertSee('No matching registration records');
    }

    public function test_inactive_user_cannot_mount_or_write_registration(): void
    {
        $this->actingAs(User::factory()->inactive()->create());
        $this->get(route('registrations.index'))->assertRedirect(route('login'));
        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_removing_optional_representative_clears_their_details(): void
    {
        $this->actingAs(User::factory()->create());
        $this->application()->set('form', $this->input())->set('representativeContact', true)
            ->set('form.representative_name', 'Synthetic Representative')->set('form.representative_contact', '09171234567')
            ->set('representativeContact', false)->assertSet('form.representative_name', '')->assertSet('form.representative_contact', '')->assertSet('authorizationFile', null);
    }

    #[TestWith(['ARCHIVED'])]
    #[TestWith(['DECEASED'])]
    #[TestWith(['TRANSFERRED'])]
    public function test_registrations_retain_existing_inactive_records_but_exclude_applications(string $status): void
    {
        $this->actingAs(User::factory()->create());
        $input = Arr::except($this->input(), ['requirements_status']);
        $pending = SeniorCitizen::create([...$input, 'registration_number' => 'SYN-PENDING']);
        $existing = SeniorCitizen::create([...$input, 'registration_number' => 'SYN-EXISTING', 'status' => $status]);

        Livewire::test(Index::class)->assertSee($existing->registration_number)->assertDontSee($pending->registration_number)
            ->assertDontSee('New ID Application')->assertDontSeeLivewire(IdApplication::class)
            ->set('stage', 'INACTIVE')->assertSee($existing->registration_number)
            ->set('search', 'SYN-PENDING')->assertDontSee($pending->registration_number);
    }

    public function test_registry_application_cancel_preserves_the_list_filters(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::withQueryParams(['status' => 'PENDING', 'search' => 'Synthetic', 'page' => 2])
            ->test(IdApplication::class)->call('cancel')
            ->assertRedirect(route('senior-citizens.index', ['search' => 'Synthetic', 'status' => 'PENDING', 'page' => 2]));
    }

    #[TestWith(['registry', 'senior-citizens.index', 'Exit to registry'])]
    #[TestWith(['registrations', 'registrations.index', 'Exit to registrations'])]
    public function test_id_navigation_keeps_the_origin_and_filters(string $from, string $route, string $exit): void
    {
        $this->actingAs(User::factory()->create());
        $senior = SeniorCitizen::create([...Arr::except($this->input(), ['requirements_status']), 'registration_number' => 'SYN-NAVIGATION']);
        $context = ['search' => 'Synthetic', 'page' => 2];

        $response = $this->get(route('senior-id.show', [$senior, 'from' => $from, ...$context]));

        $response->assertSee($exit)->assertSeeHtml('href="'.e(route($route, $context)).'"');
        $document = new \DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        foreach (['OSCA Staff navigation', 'Records navigation'] as $navigation) {
            $links = $xpath->query('//nav[@aria-label="'.$navigation.'"]//a[@aria-current="page"]');
            $this->assertCount(1, $links);
            $this->assertSame(route($route), $links->item(0)->getAttribute('href'));
        }
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
        $senior = SeniorCitizen::create([...Arr::except($input, ['requirements_status']), 'registration_number' => 'SYN-EDIT', 'status' => 'VERIFIED']);
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

        $this->assertDatabaseHas('senior_citizens', ['birth_date' => $birthDate, 'status' => 'PENDING']);
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
        $senior = SeniorCitizen::create([...Arr::except($input, ['requirements_status']), 'registration_number' => 'SYNTHETIC-CIVIL-STATUS', 'civil_status' => 'SINGLE']);
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
            'last_name' => "o'synthetic", 'name_suffix' => 'jr.', 'address' => '  synthetic street ', 'contact_number' => $number,
        ])->call('next')->call('next')->assertSet('step', 3)->assertHasNoErrors()
            ->set('informationReviewed', true)->call('submit')->assertHasNoErrors();

        $senior = SeniorCitizen::sole();
        $this->assertSame('SYNTHETIC ÑANDÚ', $senior->first_name);
        $this->assertSame('MIXED', $senior->middle_name);
        $this->assertSame("O'SYNTHETIC", $senior->last_name);
        $this->assertSame('JR.', $senior->name_suffix);
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
        $input = [...$this->input(), 'contact_number' => $number, 'applicant_type' => 'REPRESENTATIVE',
            'representative_name' => 'Synthetic Representative', 'representative_relationship' => 'Child', 'representative_contact' => $number];

        $this->application()->set('form', $input)->call('next')->call('next')->assertSet('step', 2)
            ->assertHasErrors(['form.contact_number', 'form.representative_contact']);
        $this->post(route('senior-citizens.store'), $input)->assertSessionHasErrors(['contact_number', 'representative_contact']);

        $this->assertDatabaseCount('senior_citizens', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    private function application(): Testable
    {
        return Livewire::test(IdApplication::class)
            ->set('form.requirements_status', 'REVIEW')
            ->set('form.review_reason', 'Synthetic document needs checking')
            ->call('next')->assertHasNoErrors()->assertSet('step', 1);
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

        return ['requirements_status' => 'REVIEW', 'requirements_checks' => ['identity' => false, 'residency' => false, 'authorization' => false], 'review_reason' => 'Synthetic document needs checking', 'first_name' => 'Synthetic', 'middle_name' => '', 'last_name' => 'Applicant', 'name_suffix' => '', 'birth_date' => '1950-01-01', 'sex' => 'FEMALE', 'civil_status' => '', 'barangay_id' => $barangay->id, 'address' => 'Synthetic address', 'contact_number' => '09171234567', 'applicant_type' => 'SELF', 'representative_name' => '', 'representative_relationship' => '', 'representative_contact' => ''];
    }
}
