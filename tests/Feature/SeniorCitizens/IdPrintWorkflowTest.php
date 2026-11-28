<?php

namespace Tests\Feature\SeniorCitizens;

use App\Livewire\Registrations\Index;
use App\Livewire\SeniorCitizens\IdManager;
use App\Livewire\SeniorCitizens\IdPrintManagement;
use App\Livewire\SeniorCitizens\IdReprintApprovals;
use App\Livewire\SeniorCitizens\RequestIdReprint;
use App\Models\Barangay;
use App\Models\IdPrintAttempt;
use App\Models\IdReprintRequest;
use App\Models\SeniorCitizen;
use App\Models\SeniorCitizenIdCredential;
use App\Models\User;
use App\Services\Benefits\BenefitAudit;
use App\Services\SeniorCitizens\IdCredential;
use App\Services\SeniorCitizens\IdOfficials;
use App\Services\SeniorCitizens\IdPrinting;
use App\Services\SeniorCitizens\IdReprints;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class IdPrintWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_metrics_are_static_and_refresh_while_stage_filtering_still_works(): void
    {
        $this->actingAs(User::factory()->create());
        $pending = $this->senior();
        $pending->forceFill(['id_generated_at' => null])->save();
        $issued = $this->senior(2);
        $inactive = $this->senior(3);
        $inactive->update(['status' => 'ARCHIVED']);

        $component = Livewire::test(Index::class);

        $counts = $component->viewData('counts');
        $this->assertSame([1, 1, 1], [(int) $counts->verified, (int) $counts->issued, (int) $counts->inactive]);
        $document = new \DOMDocument;
        @$document->loadHTML($component->html());
        $xpath = new \DOMXPath($document);
        $metrics = $xpath->query('//*[@aria-label="Registration metrics"]')->item(0);
        $this->assertNotNull($metrics);
        $this->assertSame(3, $xpath->query('./div', $metrics)->length);
        $this->assertSame(0, $xpath->query('.//button | .//a | .//*[@role="button" or @tabindex or @aria-pressed] | .//@*[name()="wire:click"]', $metrics)->length);

        $component->set('stage', 'VERIFIED');
        $this->assertSame([$pending->id], $component->viewData('registrations')->modelKeys());
        $component->set('stage', 'ISSUED');
        $this->assertSame([$issued->id], $component->viewData('registrations')->modelKeys());
        $component->set('stage', 'INACTIVE');
        $this->assertSame([$inactive->id], $component->viewData('registrations')->modelKeys());

        $issued->forceFill(['id_generated_at' => null])->save();
        $component->call('$refresh');
        $counts = $component->viewData('counts');
        $this->assertSame([2, 0, 1], [(int) $counts->verified, (int) $counts->issued, (int) $counts->inactive]);
        $component->assertSet('stage', 'INACTIVE');
    }

    public function test_resetting_registration_filters_returns_to_the_first_page(): void
    {
        $this->actingAs(User::factory()->create());
        $component = Livewire::test(Index::class)
            ->set('search', 'EXAMPLE')
            ->set('searchBy', 'name')
            ->set('stage', 'HISTORY')
            ->set('barangayId', '1')
            ->call('setPage', 2)
            ->assertSee('Reset filters');

        $component->call('resetFilters');

        $component->assertSet('search', '')
            ->assertSet('searchBy', 'all')
            ->assertSet('stage', '')
            ->assertSet('barangayId', '')
            ->assertSet('paginators.page', 1)
            ->assertDontSee('Reset filters');
    }

    public function test_pdf_generation_requires_physical_confirmation_and_cannot_be_repeated(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        $printing = app(IdPrinting::class);
        [$seniors, $pdf] = $printing->generate([$senior->id], 'a4');

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertSame([$senior->id], $seniors->modelKeys());
        $attempt = IdPrintAttempt::sole();
        $this->assertSame('GENERATED', $attempt->status);
        $this->assertSame('FIRST_PRINT', $attempt->purpose);
        $this->assertNull($attempt->confirmed_at);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'senior_citizen.id_print_confirmed']);
        try {
            $printing->generate([$senior->id], 'a4');
            $this->fail('An unresolved PDF must block further generation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('printing', $exception->errors());
        }
        $this->assertDatabaseCount('id_print_attempts', 1);

        $printing->confirm($attempt);

        $this->assertSame('CONFIRMED', $attempt->fresh()->status);
        $this->assertSame(auth()->id(), $attempt->fresh()->confirmed_by);
        $this->assertNotNull($attempt->fresh()->confirmed_at);
        $this->assertSame('26-0901', $senior->fresh()->osca_id_number);
        $this->assertDatabaseHas('audit_logs', ['action' => 'senior_citizen.id_print_confirmed', 'auditable_id' => $senior->id]);
    }

    public function test_printed_id_cannot_generate_another_pdf_without_approval(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        app(IdPrinting::class)->generate([$senior->id], 'a4');
        app(IdPrinting::class)->confirm(IdPrintAttempt::sole());

        $this->expectException(ValidationException::class);
        app(IdPrinting::class)->generate([$senior->id], 'a4');
    }

    public function test_confirmed_id_accepts_one_pending_request_and_preserves_its_details(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        app(IdPrinting::class)->generate([$senior->id], 'a4');
        app(IdPrinting::class)->confirm(IdPrintAttempt::sole());

        $request = app(IdReprints::class)->request($senior, 'LOST', 'Synthetic lost ID details.');

        $this->assertSame('PENDING', $request->status);
        $this->assertSame(auth()->id(), $request->requested_by);
        $this->assertSame('Synthetic lost ID details.', $request->details);
        $this->assertNull($request->reviewed_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'senior_citizen.id_reprint_requested', 'auditable_id' => $senior->id]);
        try {
            app(IdReprints::class)->request($senior, 'DAMAGED', 'Synthetic second request.');
            $this->fail('Duplicate open requests must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reason', $exception->errors());
        }
        $this->assertDatabaseCount('id_reprint_requests', 1);
    }

    public function test_staff_can_request_and_approve_their_own_reprint_for_one_successful_copy(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $senior = $this->senior();
        $originalDate = $senior->id_generated_at->toDateTimeString();
        $credentialId = SeniorCitizenIdCredential::sole()->id;
        $this->post(route('senior-id.print', $senior), ['paper' => 'a4'])->assertOk();
        $this->postJson(route('senior-id.printing.complete'), ['results' => [
            ['attempt_id' => IdPrintAttempt::sole()->id, 'result' => 'PRINTED'],
        ]])->assertOk();
        $modal = Livewire::test(IdPrintManagement::class)->call('open', $senior->id)
            ->assertDontSee('Confirm Physical Printing')->assertDontSee('PDF did not print?');

        Livewire::test(RequestIdReprint::class)->call('open', $senior->id)
            ->set('reason', 'DAMAGED')->set('details', 'Synthetic damaged card details.')->call('requestReprint')
            ->assertHasNoErrors()->assertSet('seniorId', null);
        $request = IdReprintRequest::sole();
        $this->post(route('senior-id.print', $senior), ['paper' => 'a4', 'purpose' => 'REPLACEMENT'])->assertSessionHasErrors('printing');
        Livewire::test(IdReprintApprovals::class)->call('review', $request->id, true)->assertHasNoErrors();
        $this->assertSame($staff->id, $request->fresh()->reviewed_by);
        $this->post(route('senior-id.print', $senior), ['paper' => 'a4', 'purpose' => 'FIRST_PRINT'])->assertOk();
        $reprint = IdPrintAttempt::latest('id')->firstOrFail();
        $this->assertSame('REPRINT', $reprint->purpose);
        $this->assertSame($request->id, $reprint->reprint_request_id);
        $this->assertSame('APPROVED', $request->fresh()->status);
        $this->post(route('senior-id.print', $senior), ['paper' => 'a4'])->assertSessionHasErrors('printing');
        $this->postJson(route('senior-id.printing.complete'), ['results' => [
            ['attempt_id' => $reprint->id, 'result' => 'PRINTED'],
        ]])->assertOk();

        $this->assertSame('CONSUMED', $request->fresh()->status);
        $this->assertNotNull($request->fresh()->consumed_at);
        $this->assertSame('26-0901', $senior->fresh()->osca_id_number);
        $this->assertSame($originalDate, $senior->fresh()->id_generated_at->toDateTimeString());
        $this->assertSame($credentialId, SeniorCitizenIdCredential::sole()->id);
        $this->post(route('senior-id.print', $senior), ['paper' => 'a4', 'purpose' => 'REPRINT'])->assertSessionHasErrors('printing');
        $this->assertDatabaseCount('id_print_attempts', 2);
    }

    #[TestWith(['FIRST_PRINT'])]
    #[TestWith(['REPRINT'])]
    #[TestWith(['REPLACEMENT'])]
    public function test_manual_print_purpose_cannot_bypass_approval(string $purpose): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        $this->post(route('senior-id.print', $senior), ['paper' => 'short', 'purpose' => $purpose])->assertOk();
        $this->assertSame('FIRST_PRINT', IdPrintAttempt::sole()->purpose);
        app(IdPrinting::class)->confirm(IdPrintAttempt::sole());

        $this->post(route('senior-id.print', $senior), ['paper' => 'short', 'purpose' => $purpose])->assertSessionHasErrors('printing');

        $this->assertDatabaseCount('id_print_attempts', 1);
    }

    public function test_rejection_requires_a_reason_and_keeps_printing_locked(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        app(IdPrinting::class)->generate([$senior->id], 'a4');
        app(IdPrinting::class)->confirm(IdPrintAttempt::sole());
        $request = app(IdReprints::class)->request($senior, 'LOST', 'Synthetic lost card.');

        $queue = Livewire::test(IdReprintApprovals::class)->call('review', $request->id, false)->assertHasErrors('reviewReason');
        $this->assertSame('PENDING', $request->fresh()->status);
        $queue->set('reviewReasons.'.$request->id, 'Synthetic rejection reason.')->call('review', $request->id, false)->assertHasNoErrors();

        $this->assertSame('REJECTED', $request->fresh()->status);
        $this->assertSame('Synthetic rejection reason.', $request->fresh()->review_reason);
        $this->post(route('senior-id.print', $senior), ['paper' => 'a4'])->assertSessionHasErrors('printing');
        $this->assertDatabaseHas('audit_logs', ['action' => 'senior_citizen.id_reprint_rejected']);
    }

    public function test_not_printed_result_retains_approval_for_a_retry(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        app(IdPrinting::class)->generate([$senior->id], 'a4');
        app(IdPrinting::class)->confirm(IdPrintAttempt::sole());
        $request = app(IdReprints::class)->request($senior, 'PRINTING_ERROR', 'Synthetic incorrect physical output.');
        app(IdReprints::class)->review($request, true);
        $this->post(route('senior-id.print', $senior), ['paper' => 'a4'])->assertOk();

        $this->postJson(route('senior-id.printing.complete'), ['results' => [
            ['attempt_id' => IdPrintAttempt::latest('id')->firstOrFail()->id, 'result' => 'NOT_PRINTED'],
        ]])->assertOk();
        $this->assertSame('FAILED', IdPrintAttempt::latest('id')->firstOrFail()->status);
        $this->assertSame('APPROVED', $request->fresh()->status);
        $this->post(route('senior-id.print', $senior), ['paper' => 'a4'])->assertOk();
        $this->postJson(route('senior-id.printing.complete'), ['results' => [
            ['attempt_id' => IdPrintAttempt::latest('id')->firstOrFail()->id, 'result' => 'PRINTED'],
        ]])->assertOk();

        $this->assertSame('CONSUMED', $request->fresh()->status);
        $this->assertSame(2, IdPrintAttempt::where('status', 'CONFIRMED')->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'senior_citizen.id_print_failed']);
    }

    public function test_other_staff_cannot_confirm_or_fail_someone_elses_current_attempt(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        app(IdPrinting::class)->generate([$senior->id], 'a4');
        $this->actingAs(User::factory()->create());

        foreach (['PRINTED', 'NOT_PRINTED'] as $result) {
            $this->postJson(route('senior-id.printing.complete'), ['results' => [
                ['attempt_id' => IdPrintAttempt::sole()->id, 'result' => $result],
            ]])->assertForbidden();
        }
        $this->getJson(route('senior-id.printing.pending'))->assertOk()->assertExactJson(['attempts' => []]);

        $this->assertSame('GENERATED', IdPrintAttempt::sole()->status);
    }

    public function test_admin_cannot_approve_or_generate_prints_under_the_staff_approval_policy(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('senior-id.reprint-approvals'))->assertForbidden();
        Livewire::test(IdReprintApprovals::class)->assertForbidden();
    }

    public function test_batch_request_only_generates_print_attempts_for_up_to_four_selected_ids(): void
    {
        $this->actingAs(User::factory()->create());
        $ids = array_map(fn (int $number) => $this->senior($number)->id, range(1, 5));
        $input = ['paper' => 'a4', 'layout' => 'batch', 'reviewed' => true];

        $this->postJson(route('senior-id.batch-print'), [...$input, 'senior_ids' => $ids])
            ->assertUnprocessable()->assertJsonValidationErrors('senior_ids');
        $this->assertDatabaseEmpty('id_print_attempts');

        $selected = [$ids[0], $ids[1], $ids[3], $ids[4]];
        $this->postJson(route('senior-id.batch-print'), [...$input, 'senior_ids' => array_map('strval', $selected)])
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame($selected, IdPrintAttempt::query()->orderBy('senior_citizen_id')->pluck('senior_citizen_id')->all());
    }

    public function test_batch_confirmations_are_individual_and_allow_only_approved_reprints(): void
    {
        $this->actingAs(User::factory()->create());
        $first = $this->senior();
        $second = $this->senior(2);
        $input = ['senior_ids' => [$first->id, $second->id], 'paper' => 'short', 'reviewed' => true];

        $this->postJson(route('senior-id.batch-print'), [...$input, 'senior_ids' => [(string) $first->id, (string) $second->id], 'layout' => 'batch'])
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame(2, IdPrintAttempt::where('status', 'GENERATED')->count());
        app(IdPrinting::class)->confirm($first->idPrintAttempts()->sole());
        $this->assertSame('CONFIRMED', $first->idPrintAttempts()->sole()->status);
        $this->assertSame('GENERATED', $second->idPrintAttempts()->sole()->status);
        $this->post(route('senior-id.batch-print'), $input)->assertSessionHasErrors('printing');
        app(IdPrinting::class)->confirm($second->idPrintAttempts()->sole());
        $approval = app(IdReprints::class)->request($first, 'LOST', 'Synthetic lost ID.');
        app(IdReprints::class)->review($approval, true);
        $this->post(route('senior-id.batch-print'), $input)->assertSessionHasErrors('printing');
        $this->assertDatabaseCount('id_print_attempts', 2);

        $this->post(route('senior-id.batch-print'), [...$input, 'senior_ids' => [$first->id]])->assertOk();
        $attempt = $first->idPrintAttempts()->latest('id')->firstOrFail();
        $this->assertSame('REPRINT', $attempt->purpose);
        app(IdPrinting::class)->confirm($attempt);

        $this->assertSame('CONSUMED', $approval->fresh()->status);
        $this->assertDatabaseCount('id_print_attempts', 3);
        $this->assertSame(1, $second->idPrintAttempts()->count());
    }

    public function test_historical_pdf_audits_stay_unconfirmed_until_staff_resolve_them(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        BenefitAudit::record('senior_citizen.id_print_generated', $senior, ['purpose' => 'FIRST_PRINT']);

        Livewire::test(IdPrintManagement::class)->call('open', $senior->id)->assertSee('Manage Senior Citizen ID');
        $this->assertDatabaseEmpty('id_print_attempts');
        Livewire::test(IdManager::class, ['seniorId' => $senior->id]);
        $attempt = IdPrintAttempt::sole();
        $this->assertNotNull($attempt->legacy_audit_id);
        $this->assertNull($attempt->confirmed_at);
        $this->post(route('senior-id.print', $senior), ['paper' => 'a4'])->assertSessionHasErrors('printing');
        $this->postJson(route('senior-id.printing.complete'), ['results' => [
            ['attempt_id' => $attempt->id, 'result' => 'PRINTED'],
        ]])->assertOk();

        $this->assertSame('CONFIRMED', $attempt->fresh()->status);
        $this->assertDatabaseCount('id_print_attempts', 1);
    }

    public function test_printed_ids_open_management_modal_and_never_offer_a_manual_purpose(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        app(IdPrinting::class)->generate([$senior->id], 'a4');
        app(IdPrinting::class)->confirm(IdPrintAttempt::sole());

        $this->get(route('registrations.index'))->assertSee('manage-id-print')->assertSee('Reprint Approvals');
        $this->get(route('senior-id.show', $senior))->assertSee('Manage Senior Citizen ID');
        Livewire::test(IdManager::class, ['seniorId' => $senior->id])->call('goToStep', 3)
            ->assertSee('Printing is locked.')->assertDontSeeHtml('name="purpose"')->assertDontSee('Open print-ready PDF');
    }

    public function test_request_details_are_escaped_in_the_staff_approval_queue(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        app(IdPrinting::class)->generate([$senior->id], 'a4');
        app(IdPrinting::class)->confirm(IdPrintAttempt::sole());
        $modal = Livewire::test(RequestIdReprint::class)->call('open', $senior->id)->set('reason', 'OTHER');
        $details = '<script>alert("synthetic")</script>';

        $modal->set('details', $details)->call('requestReprint')->assertHasNoErrors();

        $this->get(route('senior-id.reprint-approvals'))->assertSee($details)->assertDontSee($details, false);
    }

    public function test_reprint_request_requires_a_reason_but_accepts_blank_details(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        app(IdPrinting::class)->generate([$senior->id], 'a4');
        app(IdPrinting::class)->confirm(IdPrintAttempt::sole());

        $modal = Livewire::test(RequestIdReprint::class)->call('open', $senior->id)
            ->assertSee('Additional Details (Optional)')
            ->call('requestReprint')->assertHasErrors(['reason' => 'required']);
        $this->assertDatabaseEmpty('id_reprint_requests');

        $modal->set('reason', 'LOST')->set('details', '   ')->call('requestReprint')->assertHasNoErrors()
            ->assertSet('seniorId', null);

        $this->assertDatabaseHas('id_reprint_requests', [
            'senior_citizen_id' => $senior->id, 'reason' => 'LOST', 'details' => '', 'status' => 'PENDING',
        ]);
    }

    public function test_only_completed_prints_leave_active_registrations_and_remain_in_history_and_masterlist(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        $queue = Livewire::test(Index::class)->assertSee($senior->full_name);
        $this->get(route('senior-citizens.index'))->assertOk()->assertSee($senior->full_name);

        app(IdPrinting::class)->generate([$senior->id], 'a4');
        $queue->call('$refresh')->assertSee($senior->full_name);
        app(IdPrinting::class)->reportFailure(IdPrintAttempt::sole(), 'Synthetic canceled printing.');
        $queue->call('$refresh')->assertSee($senior->full_name);

        app(IdPrinting::class)->generate([$senior->id], 'a4');
        app(IdPrinting::class)->confirm(IdPrintAttempt::where('status', 'GENERATED')->sole());
        $queue->call('$refresh')->assertDontSee($senior->full_name)
            ->set('stage', 'HISTORY')->assertSee($senior->full_name);
        $this->get(route('senior-citizens.index'))->assertOk()->assertSee($senior->full_name);
        $this->assertDatabaseCount('senior_citizens', 1);
    }

    public function test_inactive_staff_and_guests_cannot_access_reprint_approvals(): void
    {
        $this->get(route('senior-id.reprint-approvals'))->assertRedirect(route('login'));
        $inactive = User::factory()->create(['is_active' => false]);
        $this->actingAs($inactive)->get(route('senior-id.reprint-approvals'))->assertRedirect(route('login'));
        $this->actingAs($inactive);
        Livewire::test(IdReprintApprovals::class)->assertForbidden();
    }

    public function test_stale_approval_actions_cannot_reuse_or_reverse_an_approval(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        app(IdPrinting::class)->generate([$senior->id], 'a4');
        app(IdPrinting::class)->confirm(IdPrintAttempt::sole());
        $request = app(IdReprints::class)->request($senior, 'LOST', 'Synthetic lost card.');
        $staleQueue = Livewire::test(IdReprintApprovals::class);
        Livewire::test(IdReprintApprovals::class)->call('review', $request->id, true)->assertHasNoErrors();

        $staleQueue->set('reviewReasons.'.$request->id, 'Synthetic stale rejection.')->call('review', $request->id, false)->assertStatus(409);

        $this->assertSame('APPROVED', $request->fresh()->status);
        $this->assertDatabaseCount('id_reprint_requests', 1);
    }

    public function test_database_blocks_duplicate_unresolved_attempts_and_open_requests(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        app(IdPrinting::class)->generate([$senior->id], 'a4');
        try {
            DB::transaction(function () use ($senior): void {
                $duplicate = new IdPrintAttempt;
                $duplicate->forceFill(['senior_citizen_id' => $senior->id, 'purpose' => 'FIRST_PRINT', 'status' => 'GENERATED',
                    'generated_by' => auth()->id(), 'generated_at' => now()])->save();
            });
            $this->fail('Only one unresolved attempt may exist per senior.');
        } catch (QueryException $exception) {
            $this->assertSame('23505', $exception->getCode());
        }
        $this->assertDatabaseCount('id_print_attempts', 1);
        app(IdPrinting::class)->confirm(IdPrintAttempt::sole());
        app(IdReprints::class)->request($senior, 'LOST', 'Synthetic lost card.');
        try {
            DB::transaction(function () use ($senior): void {
                $duplicate = new IdReprintRequest;
                $duplicate->forceFill(['senior_citizen_id' => $senior->id, 'requested_by' => auth()->id(), 'status' => 'PENDING',
                    'reason' => 'LOST', 'details' => 'Synthetic duplicate request.'])->save();
            });
            $this->fail('Only one open request may exist per senior.');
        } catch (QueryException $exception) {
            $this->assertSame('23505', $exception->getCode());
        }
        $this->assertDatabaseCount('id_reprint_requests', 1);
    }

    public function test_batch_completion_records_mixed_results_and_refreshes_registration_visibility(): void
    {
        $this->freezeTime();
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $first = $this->senior();
        $second = $this->senior(2);
        $response = $this->post(route('senior-id.batch-print'), [
            'senior_ids' => [$first->id, $second->id], 'paper' => 'short', 'reviewed' => true,
        ])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $attempts = IdPrintAttempt::orderBy('senior_citizen_id')->get();
        $this->assertSame($attempts->pluck('id')->all(), json_decode($response->headers->get('X-ID-Print-Attempts'), true));
        $this->assertDatabaseMissing('id_print_attempts', ['status' => 'CONFIRMED']);
        $this->getJson(route('senior-id.printing.pending'))->assertOk()->assertJsonCount(2, 'attempts')
            ->assertJsonPath('attempts.0.id', $attempts[0]->id);

        $this->postJson(route('senior-id.printing.complete'), ['results' => [
            ['attempt_id' => $attempts[0]->id, 'result' => 'PRINTED'],
            ['attempt_id' => $attempts[1]->id, 'result' => 'NOT_PRINTED'],
        ]])->assertOk();

        $this->assertDatabaseHas('id_print_attempts', ['id' => $attempts[0]->id, 'status' => 'CONFIRMED', 'confirmed_by' => $staff->id, 'confirmed_at' => now()]);
        $this->assertDatabaseHas('id_print_attempts', ['id' => $attempts[1]->id, 'status' => 'FAILED', 'confirmed_at' => null, 'failed_by' => $staff->id]);
        $this->getJson(route('senior-id.printing.pending'))->assertOk()->assertExactJson(['attempts' => []]);
        $queue = Livewire::test(Index::class);
        $this->assertSame([$second->id], $queue->viewData('registrations')->pluck('id')->all());
        $queue->set('stage', 'HISTORY');
        $this->assertSame([$first->id], $queue->viewData('registrations')->pluck('id')->all());
        $this->assertSame('Pending Printing', $second->fresh()->idPrintingStatus());
        $this->post(route('senior-id.print', $first), ['paper' => 'short'])->assertSessionHasErrors('printing');
        $this->post(route('senior-id.print', $second), ['paper' => 'short'])->assertOk();
        $this->assertDatabaseCount('senior_citizens', 2);
    }

    public function test_completion_rolls_back_every_result_if_one_attempt_is_stale(): void
    {
        $this->actingAs(User::factory()->create());
        $first = $this->senior();
        $second = $this->senior(2);
        app(IdPrinting::class)->generate([$first->id, $second->id], 'short', 'batch');
        $attempts = IdPrintAttempt::orderBy('senior_citizen_id')->get();
        app(IdPrinting::class)->reportFailure($attempts[1], 'Synthetic cancellation.');

        $this->postJson(route('senior-id.printing.complete'), ['results' => [
            ['attempt_id' => $attempts[0]->id, 'result' => 'PRINTED'],
            ['attempt_id' => $attempts[1]->id, 'result' => 'PRINTED'],
        ]])->assertConflict();

        $this->assertSame('GENERATED', $attempts[0]->fresh()->status);
        $this->assertNull($attempts[0]->fresh()->confirmed_at);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'senior_citizen.id_print_confirmed']);
    }

    public function test_completion_validates_results_before_updating_an_attempt(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        app(IdPrinting::class)->generate([$senior->id], 'short');
        $id = IdPrintAttempt::sole()->id;
        $this->postJson(route('senior-id.printing.complete'), [])->assertUnprocessable()->assertJsonValidationErrors('results');
        $this->postJson(route('senior-id.printing.complete'), ['results' => [['attempt_id' => $id, 'result' => 'UNKNOWN']]])
            ->assertUnprocessable()->assertJsonValidationErrors('results.0.result');
        $this->postJson(route('senior-id.printing.complete'), ['results' => array_fill(0, 2, ['attempt_id' => $id, 'result' => 'PRINTED'])])
            ->assertUnprocessable()->assertJsonValidationErrors('results.0.attempt_id');
        $this->postJson(route('senior-id.printing.complete'), ['results' => array_fill(0, 5, ['attempt_id' => $id, 'result' => 'PRINTED'])])
            ->assertUnprocessable()->assertJsonValidationErrors('results');
        $this->assertSame('GENERATED', IdPrintAttempt::sole()->status);
    }

    public function test_completion_endpoints_refuse_guests_and_administrators(): void
    {
        $this->getJson(route('senior-id.printing.pending'))->assertUnauthorized();
        $this->postJson(route('senior-id.printing.complete'), [])->assertUnauthorized();
        $this->actingAs(User::factory()->admin()->create());
        $this->getJson(route('senior-id.printing.pending'))->assertForbidden();
        $this->postJson(route('senior-id.printing.complete'), [])->assertForbidden();
    }

    public function test_manage_id_is_read_only_and_displays_the_selected_records_current_information(): void
    {
        $this->freezeTime();
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $first = $this->senior();
        $second = $this->senior(2);
        app(IdPrinting::class)->generate([$first->id], 'a4');
        app(IdPrinting::class)->confirm(IdPrintAttempt::sole());
        app(IdReprints::class)->request($first, 'LOST', '');
        $audits = DB::table('audit_logs')->count();

        $modal = Livewire::test(IdPrintManagement::class)->call('open', $first->id)
            ->assertSee('Manage Senior Citizen ID')->assertSee($first->full_name)->assertSee($first->osca_id_number)
            ->assertSee($staff->full_name)->assertSee(now()->format('M j, Y g:i A'))
            ->assertSee('Jan 1, 2026')->assertSee('Pending approval')
            ->assertDontSee('Request Reprint Approval')->assertDontSee('Printing Completion')
            ->assertDontSee('Edit ID Information');
        $document = new \DOMDocument;
        @$document->loadHTML($modal->html());
        $xpath = new \DOMXPath($document);
        $this->assertSame(2, $xpath->query('//button')->length);
        $this->assertSame(0, $xpath->query('//input | //select | //textarea | //form | //a')->length);
        foreach (['Senior Citizen Name', 'OSCA ID Number', 'ID Status', 'Original Issuance Date', 'Last Printed Date', 'Printed By', 'Reprint Status'] as $label) {
            $modal->assertSee($label);
        }

        $modal->call('open', $second->id)->assertSee($second->osca_id_number)->assertDontSee($first->osca_id_number)
            ->assertSee('Pending printing')->assertSee('No request')->assertDontSee($staff->full_name)
            ->call('close')->assertSet('seniorId', null)->assertDontSee('Manage Senior Citizen ID');
        $this->assertSame($audits, DB::table('audit_logs')->count());
        $this->assertDatabaseCount('id_print_attempts', 1);
        $this->assertDatabaseCount('id_reprint_requests', 1);
    }

    private function senior(int $number = 1): SeniorCitizen
    {
        Storage::fake('local');
        app(IdOfficials::class)->save(['osca_head_name' => 'Synthetic Head', 'municipal_mayor_name' => 'Synthetic Mayor'],
            ['municipal_seal' => UploadedFile::fake()->createWithContent('synthetic.jpg', file_get_contents(base_path('tests/Fixtures/id-photo.jpg')))]);
        $barangay = Barangay::create(['name' => 'Synthetic Barangay '.$number, 'code' => 'SYN-'.$number]);
        $senior = SeniorCitizen::unguarded(fn () => SeniorCitizen::create(['barangay_id' => $barangay->id,
            'first_name' => 'Synthetic', 'last_name' => 'Applicant', 'birth_date' => '1950-01-01', 'sex' => 'FEMALE',
            'address' => 'Synthetic street', 'contact_number' => '+639170000001', 'status' => 'VERIFIED',
            'verified_at' => now(), 'osca_id_number' => '26-090'.$number, 'id_generated_at' => '2026-01-01']));
        app(IdCredential::class)->createForIssued($senior);

        return $senior;
    }
}
