<?php

namespace Tests\Feature\SeniorCitizens;

use App\Livewire\Registrations\IdApplication;
use App\Livewire\SeniorCitizens\IdManager;
use App\Livewire\SeniorCitizens\IdOfficialSettings;
use App\Livewire\SeniorCitizens\RequirementsReview;
use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RequirementsReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_must_assess_requirements_before_entering_details(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(IdApplication::class)->call('next')
            ->assertSet('step', 0)->assertHasErrors('form.requirements_status')
            ->set('form.requirements_status', 'CHECKED')->call('next')
            ->assertHasErrors(['form.requirements_checks.identity', 'form.requirements_checks.residency']);

        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_checked_registration_is_verified_by_staff_and_continues_to_id_preparation(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $this->travelTo(now()->setDate(2026, 10, 14)->startOfDay());

        $component = Livewire::test(IdApplication::class)->set('form', $this->input())
            ->call('next')->call('next')->call('next')
            ->set('informationReviewed', true)->call('submit')->assertHasNoErrors();

        $senior = SeniorCitizen::sole();
        $component->assertRedirect(route('senior-id.show', [$senior, 'from' => 'registry']));
        $this->assertSame('VERIFIED', $senior->status->value);
        $this->assertSame($staff->id, $senior->verified_by);
        $this->assertTrue($senior->verified_at->equalTo(now()));
        $this->assertNull($senior->id_generated_at);
        $this->assertDatabaseHas('senior_citizen_histories', ['senior_citizen_id' => $senior->id, 'action' => 'verified']);
    }

    #[TestWith([''])]
    #[TestWith(['   '])]
    #[TestWith(['short'])]
    public function test_review_requires_a_meaningful_reason(string $reason): void
    {
        $this->actingAs(User::factory()->create());

        $payload = [...$this->input(), 'requirements_status' => 'REVIEW', 'review_reason' => $reason === 'short' ? 'abc' : $reason];
        $this->post(route('senior-citizens.store'), $payload)->assertSessionHasErrors('review_reason');

        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_unchecked_requirements_cannot_be_bypassed_at_submission(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(IdApplication::class)->set('form', $this->input())
            ->call('next')->call('next')->call('next')
            ->set('form.requirements_checks.identity', false)->set('informationReviewed', true)
            ->call('submit')->assertHasErrors('form.requirements_checks.identity')->assertSet('step', 0);

        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_http_registration_also_requires_the_document_decision(): void
    {
        $this->actingAs(User::factory()->create());
        $payload = $this->input();
        unset($payload['requirements_status']);

        $this->post(route('senior-citizens.store'), $payload)->assertSessionHasErrors('requirements_status');

        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_checked_representative_requires_both_assessment_and_authorization_proof(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $payload = [...$this->input(), 'applicant_type' => 'REPRESENTATIVE', 'representative_name' => 'Synthetic Representative', 'representative_relationship' => 'Child', 'representative_contact' => '+639170000002'];

        $this->post(route('senior-citizens.store'), $payload)->assertSessionHasErrors(['requirements_checks.authorization', 'authorization_file']);
        $this->assertDatabaseCount('senior_citizens', 0);
        $payload['requirements_checks']['authorization'] = true;
        $payload['authorization_file'] = UploadedFile::fake()->createWithContent('authorization.pdf', "%PDF-1.4\nSynthetic authorization");
        $response = $this->post(route('senior-citizens.store'), $payload)->assertSessionHasNoErrors();

        $senior = SeniorCitizen::sole();
        $response->assertRedirect(route('senior-id.show', $senior));
        $this->assertSame('VERIFIED', $senior->status->value);
        Storage::disk('local')->assertExists($senior->documents()->sole()->path);
    }

    public function test_representative_review_cannot_be_resolved_without_recorded_authorization(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->pending();
        $senior->update(['applicant_type' => 'REPRESENTATIVE', 'representative_name' => 'Synthetic Representative', 'representative_relationship' => 'Child', 'representative_contact' => '+639170000002', 'review_reason' => 'Check representative authorization']);

        Livewire::test(RequirementsReview::class, ['seniorId' => $senior->id])
            ->set('documentsChecked', true)->call('verify')->assertStatus(422);

        $this->assertNull($senior->fresh()->verified_at);
        $this->assertSame('Check representative authorization', $senior->fresh()->review_reason);
    }

    public function test_resolving_review_preserves_the_original_reason_in_history(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $reason = '<script>synthetic document concern</script>';
        $this->post(route('senior-citizens.store'), [...$this->input(), 'requirements_status' => 'REVIEW', 'review_reason' => $reason])->assertSessionHasNoErrors();
        $senior = SeniorCitizen::sole();
        $this->assertSame('PENDING', $senior->status->value);
        $this->assertNull($senior->verified_at);

        $this->get(route('senior-citizens.index', ['status' => 'PENDING']))
            ->assertSee($senior->registration_number)->assertSee('Review requirements');
        $this->get(route('registrations.index'))->assertDontSee($senior->registration_number);

        $component = Livewire::test(RequirementsReview::class, ['seniorId' => $senior->id])
            ->assertDontSee($reason, false)->call('verify')->assertHasErrors('documentsChecked')
            ->set('documentsChecked', true)->call('verify')->assertHasNoErrors()->assertDispatched('registration-verified');

        $this->assertSame('VERIFIED', $senior->fresh()->status->value);
        $this->get(route('registrations.index'))->assertSee($senior->registration_number)->assertSee('Approved / ID pending');
        $this->assertNull($senior->fresh()->review_reason);
        $this->assertSame($reason, $senior->histories()->where('action', 'verified')->sole()->changes['resolved_review_reason']);
        $this->assertDatabaseHas('senior_citizen_histories', ['senior_citizen_id' => $senior->id, 'action' => 'requirements_review_requested']);
        $component->set('documentsChecked', true)->call('verify');
        $this->assertSame(1, $senior->histories()->where('action', 'verified')->count());
    }

    public function test_legacy_pending_record_is_not_verified_on_view_and_can_be_reviewed(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->pending();

        $this->get(route('senior-citizens.show', $senior))->assertSeeLivewire(RequirementsReview::class);
        $this->assertNull($senior->fresh()->verified_at);
        Livewire::test(RequirementsReview::class, ['seniorId' => $senior->id])
            ->set('documentsChecked', true)->call('verify')->assertHasNoErrors();

        $this->assertSame('VERIFIED', $senior->fresh()->status->value);
    }

    public function test_admin_cannot_verify_requirements_through_livewire(): void
    {
        $senior = $this->pending();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(RequirementsReview::class, ['seniorId' => $senior->id])
            ->set('documentsChecked', true)->call('verify')->assertForbidden();

        $this->assertSame('PENDING', $senior->fresh()->status->value);
        $this->assertNull($senior->fresh()->verified_at);
    }

    public function test_inactive_staff_cannot_verify_requirements(): void
    {
        $senior = $this->pending();
        $this->actingAs(User::factory()->inactive()->create());

        Livewire::test(RequirementsReview::class, ['seniorId' => $senior->id])
            ->set('documentsChecked', true)->call('verify')->assertForbidden();

        $this->assertNull($senior->fresh()->verified_at);
    }

    #[TestWith(['ARCHIVED'])]
    #[TestWith(['DECEASED'])]
    public function test_review_rechecks_the_record_state_before_verifying(string $status): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->pending();
        $component = Livewire::test(RequirementsReview::class, ['seniorId' => $senior->id]);
        $senior->update(['status' => $status]);

        $component->set('documentsChecked', true)->call('verify')->assertStatus(422);

        $this->assertNull($senior->fresh()->verified_at);
        $this->assertSame($status, $senior->fresh()->status->value);
    }

    public function test_id_workspace_resolves_requirements_and_edits_officials_in_place(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->pending();

        Livewire::test(IdManager::class, ['seniorId' => $senior->id])
            ->assertSeeLivewire(RequirementsReview::class)
            ->set('officialsOpen', true)->assertSeeLivewire(IdOfficialSettings::class)
            ->dispatch('id-officials-updated')->assertSet('officialsOpen', false)->assertSet('reviewed', false);
        $response = $this->get(route('senior-id.show', [$senior, 'from' => 'registrations', 'stage' => 'PENDING', 'search' => 'Synthetic', 'page' => 2]));
        $response->assertSee(route('registrations.index', ['search' => 'Synthetic', 'stage' => 'PENDING', 'page' => 2]));
    }

    private function pending(): SeniorCitizen
    {
        $input = $this->input();
        unset($input['requirements_status']);

        return SeniorCitizen::create([...$input, 'registration_number' => 'SYNTHETIC-REVIEW', 'status' => 'PENDING']);
    }

    private function input(): array
    {
        $barangay = Barangay::create(['name' => 'Synthetic Barangay', 'code' => 'REVIEW']);

        return [
            'barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'middle_name' => '', 'last_name' => 'Applicant', 'name_suffix' => '', 'civil_status' => '',
            'birth_date' => '1950-01-01', 'sex' => 'FEMALE', 'address' => 'Synthetic address', 'contact_number' => '+639171234567',
            'applicant_type' => 'SELF', 'representative_name' => '', 'representative_relationship' => '', 'representative_contact' => '',
            'requirements_status' => 'CHECKED', 'requirements_checks' => ['identity' => true, 'residency' => true, 'authorization' => false],
        ];
    }
}
