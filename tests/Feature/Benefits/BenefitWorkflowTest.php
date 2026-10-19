<?php

namespace Tests\Feature\Benefits;

use App\Enums\BenefitStatus;
use App\Jobs\Sms\SendSmsJob;
use App\Livewire\Administration\Dashboard;
use App\Livewire\Benefits\EntitlementReport;
use App\Livewire\Benefits\Programs;
use App\Livewire\Benefits\SchedulePanel;
use App\Livewire\Benefits\SeniorRecord;
use App\Livewire\Benefits\Workspace;
use App\Models\Barangay;
use App\Models\BenefitPayment;
use App\Models\BenefitSchedule;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\User;
use App\Services\Benefits\BenefitEligibility;
use App\Services\Benefits\BenefitPayments;
use App\Services\Benefits\BenefitScheduling;
use App\Services\Benefits\BenefitSms;
use App\Services\Benefits\SeniorEligibilityRecord;
use App\Services\Reports\AdminDashboardReport;
use App\Services\SeniorCitizens\IdAssets;
use App\Services\Sms\SmsGateway;
use Database\Seeders\BenefitProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BenefitWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 12, 10)->setTime(12, 0));
        $this->actingAs(User::factory()->create());
        $this->seed(BenefitProgramSeeder::class);
    }

    public function test_verification_and_id_are_both_required_without_separate_enrollment(): void
    {
        Storage::fake('local');
        $senior = $this->senior(['status' => 'PENDING', 'verified_at' => null, 'id_generated_at' => null]);
        $records = app(SeniorEligibilityRecord::class);
        $records->verify($senior, true);
        $this->assertNotNull(app(BenefitEligibility::class)->reason($senior->fresh(), [], today()));
        $this->idPhoto($senior);
        $records->issueId($senior->fresh());
        $this->assertNull(app(BenefitEligibility::class)->reason($senior->fresh(), [], today()));
        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('beneficiaries', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'senior_citizen.id_generated', 'auditable_id' => $senior->id]);
    }

    public function test_generated_osca_ids_use_the_issuance_year_and_database_sequence(): void
    {
        Storage::fake('local');
        $first = $this->senior(['osca_id_number' => null, 'id_generated_at' => null]);
        $second = $this->senior(['osca_id_number' => null, 'id_generated_at' => null]);
        $records = app(SeniorEligibilityRecord::class);
        $this->idPhoto($first);
        $this->idPhoto($second);
        $records->issueId($first);
        $records->issueId($second);

        $this->assertMatchesRegularExpression('/^26-\d{4,}$/', $first->fresh()->osca_id_number);
        $firstSequence = (int) explode('-', $first->fresh()->osca_id_number)[1];
        $this->assertSame($firstSequence + 1, (int) explode('-', $second->fresh()->osca_id_number)[1]);
        $this->assertStringStartsWith('26-', $second->fresh()->osca_id_number);
        $this->assertDatabaseCount('osca_id_numbers', 2);

        $this->travelTo(now()->setDate(2027, 1, 1));
        $third = $this->senior(['osca_id_number' => null, 'id_generated_at' => null]);
        $this->idPhoto($third);
        $records->issueId($third);
        $this->assertStringStartsWith('27-', $third->fresh()->osca_id_number);
        $this->assertSame($firstSequence + 2, (int) explode('-', $third->fresh()->osca_id_number)[1]);
    }

    public function test_generated_osca_ids_skip_existing_numbers_and_do_not_change_existing_ids(): void
    {
        Storage::fake('local');
        $reserved = DB::table('osca_id_numbers')->insertGetId(['issued_year' => 2026, 'created_at' => now()]);
        $existingNumber = sprintf('26-%04d', $reserved + 1);
        $existing = $this->senior(['osca_id_number' => $existingNumber, 'id_generated_at' => null]);
        $new = $this->senior(['osca_id_number' => null, 'id_generated_at' => null]);
        $records = app(SeniorEligibilityRecord::class);
        $this->idPhoto($existing);
        $this->idPhoto($new);
        $records->issueId($existing);
        $records->issueId($new);

        $this->assertSame($existingNumber, $existing->fresh()->osca_id_number);
        $this->assertSame(sprintf('26-%04d', $reserved + 2), $new->fresh()->osca_id_number);
        Livewire::test(SeniorRecord::class, ['seniorId' => $new->id])->call('issueId')->assertStatus(409);
        $this->assertDatabaseCount('osca_id_numbers', 3);
    }

    #[TestWith(['26-9999'])]
    #[TestWith([null])]
    public function test_profile_updates_cannot_replace_or_clear_the_database_assigned_id(?string $number): void
    {
        $senior = $this->senior(['osca_id_number' => '26-0001']);
        $this->put(route('senior-citizens.update', $senior), [
            'barangay_id' => $senior->barangay_id, 'first_name' => $senior->first_name, 'last_name' => $senior->last_name,
            'birth_date' => $senior->birth_date->toDateString(), 'sex' => $senior->sex,
            'address' => $senior->address, 'contact_number' => $senior->contact_number, 'osca_id_number' => $number,
        ])->assertSessionHasErrors('osca_id_number');

        $this->assertSame('26-0001', $senior->fresh()->osca_id_number);
        $this->assertNotNull($senior->fresh()->id_generated_at);
    }

    public function test_december_cutoff_applies_to_verification_and_annual_aid_continues_after_90(): void
    {
        $eligible = $this->senior(['birth_date' => '1925-01-01', 'verified_at' => '2026-06-30 23:59:00', 'id_generated_at' => '2026-07-01']);
        $late = $this->senior(['verified_at' => '2026-07-01', 'id_generated_at' => '2026-07-01']);
        $this->assertNotNull(app(BenefitEligibility::class)->reason($late->fresh(), ['cutoff' => '06-30'], today()));
        $this->senior(['id_generated_at' => null]);
        $schedule = $this->schedule('december-assistance');
        $this->assertSame([$eligible->id], $schedule->entitlements()->pluck('senior_citizen_id')->all());
        $this->assertSame('500.00', $schedule->entitlements()->sole()->amount);
    }

    public function test_milestone_catchup_and_local_national_and_provincial_claims_are_independent(): void
    {
        $this->senior(['birth_date' => '1926-06-01', 'verified_at' => '2026-10-01', 'id_generated_at' => '2026-10-01']);
        foreach (['lgu-age-90', 'lgu-age-100', 'national-milestones', 'provincial-centenarian'] as $code) {
            $schedule = $this->schedule($code, ['period_key' => 'MILESTONES', 'available_funds' => '200000']);
            $entry = $schedule->entitlements()->sole();
            $this->approve($schedule);
            app(BenefitPayments::class)->claim($entry->fresh(), ['claimant_type' => 'SELF', 'external_reference' => 'SYNTHETIC-RECEIPT', 'groceries_received' => true]);
            $repeat = $this->schedule($code, ['period_key' => 'MILESTONES']);
            $this->assertSame(0, $repeat->entitlements()->count());
        }
        $this->assertDatabaseCount('benefit_payments', 4);
        $this->assertSame('100000.00', BenefitPayment::whereHas('entitlement.program', fn ($q) => $q->where('benefit_code', 'national-milestones'))->sole()->amount);
    }

    public function test_review_is_required_before_approval_and_available_funds_limit_the_list(): void
    {
        $this->senior();
        $schedule = $this->schedule('december-assistance', ['available_funds' => '499']);
        $this->assertDatabaseCount('beneficiaries', 0);
        Livewire::test(SchedulePanel::class, ['scheduleId' => $schedule->id])->call('approve')->assertHasErrors('approval');
        app(BenefitScheduling::class)->review($schedule->entitlements()->sole(), true, 'Documents checked');
        $this->assertDatabaseHas('beneficiaries', ['program_id' => $schedule->program_id, 'senior_citizen_id' => $schedule->entitlements()->sole()->senior_citizen_id, 'assigned_by' => auth()->id(), 'status' => 'ACTIVE']);
        Livewire::test(SchedulePanel::class, ['scheduleId' => $schedule->id])->call('approve')->assertHasErrors('approval');
        app(BenefitScheduling::class)->fund($schedule, '500', '500');
        app(BenefitScheduling::class)->approve($schedule);
        $this->assertSame('APPROVED', $schedule->fresh()->status);
        $this->assertSame(BenefitStatus::Unpaid, $schedule->entitlements()->sole()->status);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_approval_checks_every_recipient_across_chunk_boundaries(bool $invalidateLastRecipient): void
    {
        for ($index = 0; $index < 201; $index++) {
            $lastSenior = $this->senior();
        }
        $schedule = $this->schedule('december-assistance', ['available_funds' => '200000']);
        $schedule->entitlements()->update(['reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        if ($invalidateLastRecipient) {
            $lastSenior->update(['status' => 'DECEASED']);
        }

        if ($invalidateLastRecipient) {
            try {
                app(BenefitScheduling::class)->approve($schedule);
                $this->fail('Approval must reject an ineligible recipient in the second chunk.');
            } catch (ValidationException $exception) {
                $this->assertSame(['The senior record is not verified.'], $exception->errors()['approval']);
            }

            $this->assertSame('DRAFT', $schedule->fresh()->status);
            $this->assertSame(201, $schedule->entitlements()->where('status', 'PENDING')->count());
            $this->assertDatabaseMissing('audit_logs', ['action' => 'benefit.schedule_approved', 'auditable_id' => $schedule->id]);
        } else {
            app(BenefitScheduling::class)->approve($schedule);

            $this->assertSame('APPROVED', $schedule->fresh()->status);
            $this->assertSame(201, $schedule->entitlements()->where('status', 'UNPAID')->count());
            $this->assertDatabaseHas('audit_logs', ['action' => 'benefit.schedule_approved', 'auditable_id' => $schedule->id]);
        }
    }

    public function test_unique_reference_and_payment_confirmation_prevent_double_release(): void
    {
        $this->senior();
        $schedule = $this->schedule();
        $entry = $schedule->entitlements()->sole();
        $this->approve($schedule);
        Livewire::test(SchedulePanel::class, ['scheduleId' => $schedule->id])->call('claim', $entry->id)->assertHasErrors('paymentConfirmed');
        Livewire::test(SchedulePanel::class, ['scheduleId' => $schedule->id])->set('paymentConfirmed', true)->call('claim', $entry->id)->assertHasNoErrors();
        $payment = BenefitPayment::sole();
        $this->assertStringStartsWith('PAY-', $payment->reference_number);
        $this->assertSame(BenefitStatus::Claimed, $entry->fresh()->status);
        Livewire::test(SchedulePanel::class, ['scheduleId' => $schedule->id])->set('paymentConfirmed', true)->call('claim', $entry->id)->assertStatus(409);
        $this->assertDatabaseCount('benefit_payments', 1);
    }

    public function test_unclaimed_rescheduling_preserves_period_amount_and_prior_history(): void
    {
        $this->senior();
        $first = $this->schedule();
        $entry = $first->entitlements()->sole();
        $this->approve($first);
        app(BenefitScheduling::class)->close($first);
        $this->assertSame(BenefitStatus::Unclaimed, $entry->fresh()->status);
        $entry->program->update(['default_amount' => 900]);
        $second = $this->schedule('december-assistance', ['amount' => '900', 'period_key' => '2025']);
        app(BenefitScheduling::class)->reschedule($entry->fresh(), $second);
        $this->assertSame('2026', $entry->fresh()->period_key);
        $this->assertSame('500.00', $entry->fresh()->amount);
        $this->assertSame(BenefitStatus::Pending, $entry->fresh()->status);
        $this->assertNull($entry->fresh()->reviewed_at);
        $this->assertDatabaseHas('benefit_schedule_recipients', ['benefit_schedule_id' => $first->id, 'benefit_entitlement_id' => $entry->id, 'outcome' => 'UNCLAIMED']);
    }

    public function test_excluded_recipients_remain_pending_and_regeneration_does_not_undo_review(): void
    {
        $this->senior();
        $schedule = $this->schedule();
        $entry = $schedule->entitlements()->sole();
        app(BenefitScheduling::class)->review($entry, false, 'Awaiting downloaded funds');
        app(BenefitScheduling::class)->prepare($schedule);
        $this->assertNull($entry->fresh()->benefit_schedule_id);
        $this->assertSame(BenefitStatus::Pending, $entry->fresh()->status);
        $this->assertSame(0, $schedule->entitlements()->count());
    }

    public function test_admin_reversal_preserves_original_payment_and_allows_audited_reclaim(): void
    {
        $this->senior();
        $schedule = $this->schedule();
        $this->approve($schedule);
        $payment = app(BenefitPayments::class)->claim($schedule->entitlements()->sole(), ['claimant_type' => 'SELF']);
        $reference = $payment->reference_number;
        $staff = auth()->user();
        $this->actingAs(User::factory()->admin()->create());
        app(BenefitPayments::class)->reverse($payment, 'Correcting an erroneous release');
        $this->assertNotNull($payment->fresh()->reversed_at);
        $this->assertSame($reference, $payment->fresh()->reference_number);
        $this->assertDatabaseHas('audit_logs', ['action' => 'benefit.payment_reversed', 'auditable_id' => $payment->id]);
        $this->actingAs($staff);
        $replacement = app(BenefitPayments::class)->claim($schedule->entitlements()->sole(), ['claimant_type' => 'SELF']);
        $this->assertNotSame($reference, $replacement->reference_number);
        $this->assertDatabaseCount('benefit_payments', 2);
    }

    public function test_deceased_record_retains_entitlement_but_blocks_claims_and_payout_sms(): void
    {
        $senior = $this->senior();
        $schedule = $this->schedule();
        $this->approve($schedule);
        $senior->update(['status' => 'DECEASED', 'died_on' => today()]);
        Livewire::test(SchedulePanel::class, ['scheduleId' => $schedule->id])->set('paymentConfirmed', true)->call('claim', $schedule->entitlements()->sole()->id)->assertHasErrors('claim');
        $this->assertSame(0, app(BenefitSms::class)->preview($schedule->fresh(), 'Program [PERIOD] PHP [AMOUNT]', null)['eligible']);
        $this->assertDatabaseCount('benefit_entitlements', 1);
        $this->assertDatabaseCount('benefit_payments', 0);
    }

    public function test_october_classifications_require_verification_and_no_pension(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 10));
        $eligible = $this->senior(['is_bedridden' => true, 'is_indigent' => true, 'classification_verified_at' => now()]);
        $this->senior(['is_bedridden' => true, 'is_indigent' => true]);
        $this->senior(['is_bedridden' => true, 'is_indigent' => true, 'classification_verified_at' => now(), 'has_local_pension' => true]);
        $schedule = $this->schedule('october-assistance', ['amount' => '300']);
        $this->assertSame([$eligible->id], $schedule->entitlements()->pluck('senior_citizen_id')->all());
    }

    public function test_both_pension_programs_cover_separate_cycles(): void
    {
        $this->senior(['has_national_pension' => true, 'has_local_pension' => true]);
        $national = $this->schedule('national-social-pension', ['period_key' => '2026-Q4', 'amount' => null]);
        $local = $this->schedule('local-social-pension', ['period_key' => '2026-H2']);
        $this->assertNull($national->entitlements()->sole()->amount);
        app(BenefitScheduling::class)->fund($national, '1000', '1000');
        $this->assertSame('1000.00', $national->entitlements()->sole()->amount);
        $this->assertSame('500.00', $local->entitlements()->sole()->amount);
    }

    public function test_expired_national_window_is_checked_again_at_release(): void
    {
        $this->senior(['birth_date' => '1945-12-11']);
        $schedule = $this->schedule('national-milestones', ['period_key' => 'MILESTONES']);
        $this->approve($schedule);
        $this->travelTo(now()->setDate(2026, 12, 12));
        Livewire::test(SchedulePanel::class, ['scheduleId' => $schedule->id])->set('paymentConfirmed', true)->set('externalReference', 'SYNTHETIC-RECEIPT')->call('claim', $schedule->entitlements()->sole()->id)->assertHasErrors('claim');
    }

    public function test_sms_preview_is_scoped_uses_representative_contact_and_cannot_be_replayed(): void
    {
        Queue::fake();
        $senior = $this->senior(['contact_number' => null, 'representative_contact' => '+639171234567']);
        $this->senior();
        $schedule = $this->schedule('december-assistance', ['barangay_ids' => [$senior->barangay_id]]);
        $this->approve($schedule);
        $service = app(BenefitSms::class);
        $message = $service->template($schedule);
        $preview = $service->preview($schedule->fresh(), $message, $senior->barangay_id);
        $this->assertSame(1, $preview['eligible']);
        $this->assertStringContainsString('PHP 500.00', $preview['example']);
        $blast = $service->send($schedule, $message, $senior->barangay_id, $preview['hash']);
        $this->assertDatabaseHas('sms_messages', ['sms_blast_id' => $blast->id, 'recipient_number' => '+639171234567', 'benefit_entitlement_id' => $schedule->entitlements()->sole()->id]);
        $this->expectException(ValidationException::class);
        $service->send($schedule, $message, $senior->barangay_id, $preview['hash']);
    }

    public function test_changed_recipients_invalidate_sms_approval(): void
    {
        Queue::fake();
        $senior = $this->senior();
        $schedule = $this->schedule();
        $this->approve($schedule);
        $service = app(BenefitSms::class);
        $preview = $service->preview($schedule->fresh(), 'Benefit [PERIOD]', null);
        $senior->update(['contact_number' => '+639181234567']);
        $this->expectException(ValidationException::class);
        $service->send($schedule, 'Benefit [PERIOD]', null, $preview['hash']);
    }

    public function test_admin_can_review_but_cannot_manage_staff_workflow(): void
    {
        $senior = $this->senior();
        $schedule = $this->schedule();
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('benefits.workspace'))->assertOk();
        Livewire::test(Programs::class)->call('installCatalog')->assertForbidden();
        Livewire::test(SeniorRecord::class, ['seniorId' => $senior->id])->call('issueId')->assertForbidden();
        Livewire::test(SchedulePanel::class, ['scheduleId' => $schedule->id])->call('approve')->assertForbidden();
        Livewire::test(Workspace::class)->call('createSchedule')->assertForbidden();
    }

    public function test_livewire_pages_render_database_values_and_protect_schedule_identity(): void
    {
        $senior = $this->senior();
        $schedule = $this->schedule();
        $this->get(route('benefits.workspace'))->assertOk()->assertSee('Benefit Reports and Rescheduling');
        $this->get(route('benefits.programs'))->assertOk()->assertSee('Expanded Centenarians Act');
        $this->get(route('senior-citizens.show', $senior))->assertOk()->assertSee('Supporting Documents');
        Livewire::test(SchedulePanel::class, ['scheduleId' => $schedule->id])->assertSee($schedule->reference_number)->assertSee($senior->full_name);
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(SchedulePanel::class, ['scheduleId' => $schedule->id])->set('scheduleId', $schedule->id + 1);
    }

    public function test_managed_program_cannot_bypass_staff_review_through_legacy_schedule_endpoint(): void
    {
        $program = $this->program('december-assistance');
        $this->post(route('payout-schedules.store'), ['program_id' => $program->id, 'reference_number' => 'LEGACY-BYPASS', 'scheduled_on' => today()->toDateString(), 'amount' => 500])->assertSessionHasErrors('program_id');
        $this->assertDatabaseCount('payout_schedules', 0);
    }

    public function test_representative_registration_requires_authorization_and_keeps_upload_private(): void
    {
        Storage::fake('local');
        $barangay = Barangay::create(['name' => 'Synthetic Barangay', 'code' => 'REP']);
        $payload = ['requirements_status' => 'REVIEW', 'requirements_checks' => ['identity' => false, 'residency' => false, 'authorization' => false], 'review_reason' => 'Synthetic document needs checking', 'barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => 'Representative Applicant', 'birth_date' => '1950-01-01', 'sex' => 'FEMALE', 'address' => 'Synthetic address', 'contact_number' => '09170000001', 'applicant_type' => 'REPRESENTATIVE', 'representative_name' => 'Synthetic Representative', 'representative_relationship' => 'Child', 'representative_contact' => '09171234567'];
        $this->post(route('senior-citizens.store'), $payload)->assertSessionHasErrors('authorization_file');
        $this->post(route('senior-citizens.store'), [...$payload, 'authorization_file' => UploadedFile::fake()->createWithContent('authorization.pdf', "%PDF-1.4\nSynthetic authorization")])->assertSessionHasNoErrors();
        $senior = SeniorCitizen::sole();
        $document = $senior->documents()->sole();
        Storage::disk('local')->assertExists($document->path);
        $this->get(route('senior-citizens.document', [$senior, $document->id]))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $other = $this->senior();
        $this->get(route('senior-citizens.document', [$other, $document->id]))->assertNotFound();
        auth()->logout();
        $this->get(route('senior-citizens.document', [$senior, $document->id]))->assertRedirect(route('login'));
    }

    public function test_representative_claiming_requires_recorded_proof_and_schedule_permission(): void
    {
        $senior = $this->senior(['representative_name' => 'Synthetic Representative', 'representative_relationship' => 'Child', 'representative_contact' => '+639171234567']);
        $schedule = $this->schedule('december-assistance', ['allow_representative' => true]);
        $this->approve($schedule);
        Livewire::test(SchedulePanel::class, ['scheduleId' => $schedule->id])->set('claimant', 'REPRESENTATIVE')->set('paymentConfirmed', true)->call('claim', $schedule->entitlements()->sole()->id)->assertStatus(422);
        $senior->documents()->create(['document_type' => 'AUTHORIZATION', 'path' => 'synthetic.pdf', 'original_name' => 'synthetic.pdf', 'mime_type' => 'application/pdf', 'size' => 1, 'uploaded_by' => auth()->id()]);
        Livewire::test(SchedulePanel::class, ['scheduleId' => $schedule->id])->set('claimant', 'REPRESENTATIVE')->set('paymentConfirmed', true)->call('claim', $schedule->entitlements()->sole()->id)->assertHasNoErrors();
        $this->assertDatabaseHas('benefit_payments', ['claimant_type' => 'REPRESENTATIVE']);
    }

    #[TestWith(['requirements'])]
    #[TestWith(['future'])]
    #[TestWith(['inactive'])]
    public function test_release_rechecks_schedule_requirements_date_and_program_status(string $case): void
    {
        $this->senior();
        $attributes = match ($case) {
            'requirements' => ['requirements' => 'Bring OSCA ID'],
            'future' => ['scheduled_at' => now()->addDay()],
            default => [],
        };
        $schedule = $this->schedule('december-assistance', $attributes);
        $this->approve($schedule);
        if ($case === 'inactive') {
            $schedule->program->update(['status' => 'INACTIVE']);
        }
        $component = Livewire::test(SchedulePanel::class, ['scheduleId' => $schedule->id])->set('paymentConfirmed', true)->call('claim', $schedule->entitlements()->sole()->id);
        if ($case === 'requirements') {
            $component->assertHasErrors('claim');
        } else {
            $component->assertStatus(422);
        }
        $this->assertDatabaseCount('benefit_payments', 0);
    }

    public function test_provincial_tracking_requires_receipt_and_groceries_confirmation(): void
    {
        $this->senior(['birth_date' => '1926-01-01']);
        $schedule = $this->schedule('provincial-centenarian', ['period_key' => 'MILESTONES']);
        $this->approve($schedule);
        Livewire::test(SchedulePanel::class, ['scheduleId' => $schedule->id])->set('paymentConfirmed', true)->call('claim', $schedule->entitlements()->sole()->id)->assertHasErrors('claim')
            ->set('externalReference', 'SYNTHETIC-PROVINCIAL-RECEIPT')->call('claim', $schedule->entitlements()->sole()->id)->assertHasErrors('claim')
            ->set('groceriesReceived', true)->call('claim', $schedule->entitlements()->sole()->id)->assertHasNoErrors();
        $this->assertDatabaseHas('benefit_payments', ['external_reference' => 'SYNTHETIC-PROVINCIAL-RECEIPT', 'groceries_received' => true]);
    }

    public function test_tss_requires_office_request_verification(): void
    {
        $senior = $this->senior();
        $schedule = $this->schedule('tss', ['period_key' => '2026-REQUEST-1', 'amount' => '1000']);
        $this->assertSame(0, $schedule->entitlements()->count());
        app(SeniorEligibilityRecord::class)->classify($senior, ['is_bedridden' => false, 'is_indigent' => false, 'has_national_pension' => false, 'has_local_pension' => false, 'tss_request' => 'Synthetic confinement checked by office']);
        app(BenefitScheduling::class)->prepare($schedule);
        $this->assertSame(1, $schedule->entitlements()->count());
    }

    public function test_existing_amount_survives_rule_changes_and_approved_funding_is_immutable(): void
    {
        $this->senior();
        $schedule = $this->schedule();
        $schedule->program->update(['default_amount' => 700, 'benefit_rules' => ['cadence' => 'ANNUAL']]);
        app(BenefitScheduling::class)->fund($schedule, '700', '1000');
        $this->assertSame('500.00', $schedule->entitlements()->sole()->amount);
        $this->assertSame('06-30', $schedule->fresh()->rule_snapshot['cutoff']);
        $this->approve($schedule);
        Livewire::test(SchedulePanel::class, ['scheduleId' => $schedule->id])->set('amount', '900')->set('funds', '2000')->call('fund')->assertStatus(409);
    }

    public function test_staff_cannot_reverse_payment_or_claim_a_recipient_from_another_schedule(): void
    {
        $senior = $this->senior();
        $schedule = $this->schedule();
        $this->approve($schedule);
        $entry = $schedule->entitlements()->sole();
        $other = $this->schedule('december-assistance', ['barangay_ids' => [$senior->barangay_id]]);
        Livewire::test(SchedulePanel::class, ['scheduleId' => $other->id])->set('paymentConfirmed', true)->call('claim', $entry->id)->assertNotFound();
        $payment = app(BenefitPayments::class)->claim($entry, ['claimant_type' => 'SELF']);
        Livewire::test(EntitlementReport::class)->set('reason', 'Erroneous recorded payment')->call('reverse', $payment->id)->assertForbidden();
        $this->assertNull($payment->fresh()->reversed_at);
    }

    public function test_admin_graphs_and_program_cards_include_new_payments_and_exclude_reversals(): void
    {
        $this->senior();
        $schedule = $this->schedule();
        $this->approve($schedule);
        $payment = app(BenefitPayments::class)->claim($schedule->entitlements()->sole(), ['claimant_type' => 'SELF']);
        $report = app(AdminDashboardReport::class);
        $this->assertSame(500.0, $report->distribution('Monthly')['released']);
        $this->assertSame(500.0, $report->distribution('Quarterly')['planned']);
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(Dashboard::class)->call('navigate', 'programs')->assertSee('PHP 500.00 released');
        app(BenefitPayments::class)->reverse($payment, 'Erroneously confirmed payment');
        $this->assertSame(0.0, $report->distribution('Monthly')['released']);
    }

    public function test_deceased_identity_cannot_be_registered_again(): void
    {
        $senior = $this->senior(['status' => 'DECEASED']);
        $this->post(route('senior-citizens.store'), [
            'barangay_id' => $senior->barangay_id, 'first_name' => $senior->first_name, 'last_name' => $senior->last_name,
            'birth_date' => $senior->birth_date->toDateString(), 'sex' => 'FEMALE', 'address' => 'Synthetic address', 'contact_number' => '+639171234567',
        ])->assertSessionHasErrors('first_name');
        $this->assertDatabaseCount('senior_citizens', 1);
    }

    public function test_id_printing_requires_verified_record_and_recorded_issuance(): void
    {
        Storage::fake('local');
        $senior = $this->senior(['id_generated_at' => null]);
        $this->get(route('senior-citizens.id-card', $senior))->assertStatus(422);
        $this->idPhoto($senior);
        app(SeniorEligibilityRecord::class)->issueId($senior);
        $this->get(route('senior-citizens.id-card', $senior))->assertOk()->assertSee($senior->osca_id_number)->assertHeader('Cache-Control', 'no-store, private');
        $senior->update(['status' => 'DECEASED']);
        $this->get(route('senior-citizens.id-card', $senior))->assertStatus(422);
    }

    #[TestWith(['=1+1'])]
    #[TestWith(["\x1b=1+1"])]
    #[TestWith(["\tSynthetic"])]
    public function test_filtered_report_export_escapes_formulas_and_retains_original_period(string $programName): void
    {
        $this->senior();
        $schedule = $this->schedule();
        $schedule->program->update(['name' => $programName]);
        Livewire::test(EntitlementReport::class)->set('status', 'PENDING')->set('period', '2026')->call('export')->assertFileDownloaded('benefit-entitlements.csv');
        $response = app(EntitlementReport::class)->export();
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();
        $this->assertStringContainsString("'".$programName, $csv);
        $this->assertStringContainsString('2026', $csv);
        $this->assertDatabaseHas('audit_logs', ['action' => 'benefit.report_exported']);
    }

    public function test_clearing_a_schedule_program_resets_defaults_without_a_database_error(): void
    {
        $program = $this->program('december-assistance');

        Livewire::test(Workspace::class)
            ->set('form.program_id', (string) $program->id)
            ->assertSet('form.amount', $program->default_amount)
            ->set('form.program_id', '')
            ->assertHasNoErrors()
            ->assertSet('form.amount', '')
            ->assertSet('form.period_key', '2026');
    }

    #[TestWith(['not-an-id'])]
    #[TestWith([['unexpected']])]
    public function test_schedule_program_selection_validates_before_querying(mixed $programId): void
    {
        Livewire::test(Workspace::class)
            ->set('form.program_id', $programId)
            ->assertHasErrors(['form.program_id' => 'integer']);
    }

    #[TestWith([Workspace::class, 'schedules', 'schedulesPage', 2])]
    #[TestWith([SchedulePanel::class, 'entries', 'recipientsPage', 3])]
    #[TestWith([EntitlementReport::class, 'entries', 'entitlementsPage', 4])]
    public function test_benefit_tables_use_independent_pagination(string $component, string $variable, string $pageName, int $page): void
    {
        $schedule = $this->schedule();

        $instance = Livewire::withQueryParams(['page' => 8, 'schedulesPage' => 2, 'recipientsPage' => 3, 'entitlementsPage' => 4])
            ->test($component, $component === SchedulePanel::class ? ['scheduleId' => $schedule->id] : []);

        $this->assertSame($pageName, $instance->viewData($variable)->getPageName());
        $this->assertSame($page, $instance->viewData($variable)->currentPage());
    }

    public function test_changing_report_filters_resets_only_report_pagination(): void
    {
        $component = Livewire::test(EntitlementReport::class)
            ->call('gotoPage', 3, 'entitlementsPage')
            ->set('status', 'PENDING');

        $this->assertSame(1, $component->viewData('entries')->currentPage());
    }

    public function test_queued_sms_cannot_announce_a_previous_schedule_after_rescheduling(): void
    {
        Queue::fake();
        $this->senior();
        $first = $this->schedule();
        $this->approve($first);
        $sms = app(BenefitSms::class);
        $body = $sms->template($first);
        $preview = $sms->preview($first->fresh(), $body, null);
        $blast = $sms->send($first, $body, null, $preview['hash']);
        $message = $blast->messages()->sole();
        app(BenefitScheduling::class)->close($first);
        $second = $this->schedule();
        app(BenefitScheduling::class)->reschedule($first->entitlements()->sole(), $second);
        $this->approve($second);
        $gateway = $this->mock(SmsGateway::class);
        $gateway->shouldNotReceive('send');
        (new SendSmsJob($message->id))->handle($gateway);
        $this->assertSame('FAILED', $message->fresh()->status->value);
        $this->post(route('sms.messages.retry', $message))->assertStatus(422);
        $this->assertSame(BenefitStatus::Unpaid, $second->entitlements()->sole()->status);
    }

    private function senior(array $attributes = []): SeniorCitizen
    {
        $barangay = Barangay::create(['name' => 'Synthetic Barangay '.Barangay::count(), 'code' => 'T'.Barangay::count()]);

        return SeniorCitizen::unguarded(fn () => SeniorCitizen::create([
            'barangay_id' => $barangay->id, 'registration_number' => 'TEST-'.SeniorCitizen::count(),
            'first_name' => 'Synthetic', 'last_name' => 'Applicant '.SeniorCitizen::count(), 'birth_date' => '1950-01-01',
            'sex' => 'FEMALE', 'address' => 'Synthetic address', 'contact_number' => '+639171234567',
            'status' => 'VERIFIED', 'verified_at' => '2026-01-01', 'osca_id_number' => 'ID-'.SeniorCitizen::count(), 'id_generated_at' => '2026-01-01', ...$attributes,
        ]));
    }

    private function idPhoto(SeniorCitizen $senior): void
    {
        app(IdAssets::class)->save($senior, UploadedFile::fake()->createWithContent('synthetic.jpg', file_get_contents(base_path('tests/Fixtures/id-photo.jpg'))), 'PHOTO');
    }

    private function program(string $code): Program
    {
        $program = Program::where('benefit_code', $code)->sole();
        $program->update(['status' => 'ACTIVE']);

        return $program;
    }

    private function schedule(string $code = 'december-assistance', array $attributes = []): BenefitSchedule
    {
        $program = $this->program($code);

        return app(BenefitScheduling::class)->create($program, [
            'period_key' => '2026', 'eligibility_on' => today()->toDateString(), 'scheduled_at' => now()->subHour()->toDateTimeString(),
            'location' => 'Synthetic payout venue', 'barangay_ids' => [], 'allow_representative' => false,
            'amount' => $program->default_amount, 'available_funds' => '10000', ...$attributes,
        ]);
    }

    private function approve(BenefitSchedule $schedule): void
    {
        foreach ($schedule->entitlements()->get() as $entry) {
            app(BenefitScheduling::class)->review($entry, true, 'Verified supporting records');
        }
        app(BenefitScheduling::class)->approve($schedule);
    }
}
