<?php

namespace Tests\Feature\SeniorCitizens;

use App\Livewire\Benefits\SeniorRecord;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\Benefits\BenefitAssignment;
use App\Services\SeniorCitizens\IdCardLayout;
use App\Services\SeniorCitizens\IdCredential;
use App\Services\SeniorCitizens\IdOfficials;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class OscaConsultationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_contact_even_with_a_representative_number(): void
    {
        $senior = $this->senior();
        $this->actingAs(User::factory()->create());

        $this->post(route('senior-citizens.store'), [
            ...$this->registration($senior), 'contact_number' => '', 'representative_contact' => '09170000002',
        ])->assertSessionHasErrors('contact_number');

        $this->assertDatabaseCount('senior_citizens', 1);
    }

    public function test_distinct_middle_names_register_separately_and_full_identity_duplicates_are_rejected(): void
    {
        $senior = $this->senior(['middle_name' => 'Alpha']);
        $this->actingAs(User::factory()->create());
        $input = [...$this->registration($senior), 'middle_name' => 'Beta'];

        $this->post(route('senior-citizens.store'), $input)->assertSessionHasNoErrors();
        $this->post(route('senior-citizens.store'), [...$input, 'middle_name' => 'beta'])->assertSessionHasErrors('first_name');

        $this->assertDatabaseCount('senior_citizens', 2);
        $this->assertDatabaseHas('senior_citizens', ['middle_name' => 'BETA']);
    }

    public function test_profile_edits_check_middle_names_and_prevent_identity_collisions(): void
    {
        $first = $this->senior(['middle_name' => 'Alpha']);
        $second = $this->senior(['middle_name' => 'Beta']);
        $this->actingAs(User::factory()->create());

        $this->put(route('senior-citizens.update', $second), $this->registration($second))->assertSessionHasNoErrors();
        $this->put(route('senior-citizens.update', $second), $this->registration($first))->assertSessionHasErrors('first_name');

        $this->assertSame('BETA', $second->fresh()->middle_name);
    }

    public function test_masterlist_filters_programs_and_pensions_and_searches_middle_names(): void
    {
        $this->actingAs(User::factory()->create());
        $assigned = $this->senior(['middle_name' => 'Searchable', 'has_national_pension' => true]);
        $unassigned = $this->senior(['middle_name' => 'Other']);
        $program = $this->program();
        app(BenefitAssignment::class)->assign($assigned, $program);

        $this->get(route('senior-citizens.index', ['program_id' => $program->id, 'sort' => 'benefits', 'direction' => 'asc']))
            ->assertViewHas('seniorCitizens', fn ($records) => $records->modelKeys() === [$assigned->id])->assertSee('Pensioner')->assertSee($program->name);
        $this->get(route('senior-citizens.index', ['benefit' => 'NONE']))
            ->assertViewHas('seniorCitizens', fn ($records) => $records->modelKeys() === [$unassigned->id]);
        $this->get(route('senior-citizens.index', ['benefit' => 'PENSIONER', 'search' => 'searchable']))
            ->assertViewHas('seniorCitizens', fn ($records) => $records->modelKeys() === [$assigned->id]);
    }

    public function test_masterlist_sorts_program_names_and_rejects_sql_sort_input(): void
    {
        $this->actingAs(User::factory()->create());
        $first = $this->senior();
        $second = $this->senior();
        app(BenefitAssignment::class)->assign($first, $this->program('Zeta'));
        app(BenefitAssignment::class)->assign($second, $this->program('Alpha'));

        $this->get(route('senior-citizens.index', ['sort' => 'benefits', 'direction' => 'asc']))
            ->assertViewHas('seniorCitizens', fn ($records) => $records->modelKeys() === [$second->id, $first->id]);
        $this->getJson(route('senior-citizens.index', ['sort' => 'id; drop table senior_citizens', 'direction' => 'sideways']))
            ->assertUnprocessable()->assertJsonValidationErrors(['sort', 'direction']);
        $this->assertDatabaseCount('senior_citizens', 2);
    }

    #[TestWith(['id_generated_at'])]
    #[TestWith(['osca_id_number'])]
    #[TestWith(['verified_at'])]
    public function test_benefit_assignment_requires_verification_and_generated_id(string $missing): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior([$missing => null]);
        $program = $this->program();

        Livewire::test(SeniorRecord::class, ['seniorId' => $senior->id])->set('programId', (string) $program->id)
            ->call('assignBenefit')->assertStatus(422);
        $this->assertDatabaseCount('beneficiaries', 0);
    }

    public function test_staff_can_assign_once_and_admin_cannot_assign(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $senior = $this->senior();
        $program = $this->program();

        Livewire::test(SeniorRecord::class, ['seniorId' => $senior->id])->set('programId', (string) $program->id)->call('assignBenefit')->assertHasNoErrors()->assertSee($program->name);
        app(BenefitAssignment::class)->assign($senior, $program);

        $this->assertDatabaseCount('beneficiaries', 1);
        $this->assertDatabaseHas('beneficiaries', ['senior_citizen_id' => $senior->id, 'program_id' => $program->id, 'assigned_by' => $staff->id]);
        $this->flushSession();
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(SeniorRecord::class, ['seniorId' => $senior->id])->set('programId', (string) $program->id)->call('assignBenefit')->assertForbidden();
    }

    public function test_classification_requires_generated_id(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior(['id_generated_at' => null]);

        Livewire::test(SeniorRecord::class, ['seniorId' => $senior->id])->call('saveClassifications')->assertStatus(422);

        $this->assertNull($senior->fresh()->classification_verified_at);
    }

    public function test_external_transfer_records_requester_and_cancels_local_id_and_benefits(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $senior = $this->senior();
        $credential = app(IdCredential::class)->createForIssued($senior);
        app(BenefitAssignment::class)->assign($senior, $this->program());

        $this->post(route('senior-citizens.transfers.store', $senior), $this->externalTransfer())->assertSessionHasNoErrors()->assertRedirect();

        $transfer = $senior->transfers()->sole();
        $this->assertSame('TRANSFERRED', $senior->fresh()->status->value);
        $this->assertSame('Synthetic Requester', $transfer->requester_name);
        $this->assertSame('Synthetic street, Synthetic origin, Santa Maria, Bulacan', $transfer->previous_location);
        $this->assertSame('Synthetic destination street, Synthetic destination barangay, Synthetic municipality, Synthetic province', $transfer->destination);
        $this->assertSame($staff->id, $transfer->recorded_by);
        $this->assertSame('INACTIVE', Beneficiary::sole()->status);
        $this->assertFalse(app(IdCredential::class)->valid($credential->fresh()));
        $this->post(route('senior-id.print', $senior), ['paper' => 'short', 'purpose' => 'REPRINT'])->assertStatus(422);
        $this->get(route('senior-citizens.show', [$senior, 'section' => 'transfers']))->assertSee('Transfer history')->assertSee($transfer->destination)->assertSee('Certificate of Cancellation');
        $this->assertDatabaseHas('senior_citizen_histories', ['senior_citizen_id' => $senior->id, 'action' => 'transferred']);
    }

    public function test_internal_transfer_updates_address_and_retains_immutable_origin_history(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        $barangay = Barangay::create(['name' => 'Synthetic destination', 'code' => 'DEST']);
        $input = ['type' => 'INTERNAL', 'requested_by' => 'SELF', 'barangay_id' => $barangay->id, 'destination_address' => 'Synthetic next street', 'transferred_on' => today()->toDateString()];

        $this->post(route('senior-citizens.transfers.store', $senior), $input)->assertSessionHasNoErrors();
        $first = $senior->transfers()->sole();
        $this->post(route('senior-citizens.transfers.store', $senior), [...$input, 'destination_address' => 'Synthetic final street'])->assertSessionHasNoErrors();

        $this->assertSame('VERIFIED', $senior->fresh()->status->value);
        $this->assertSame($barangay->id, $senior->fresh()->barangay_id);
        $this->assertSame('Synthetic final street', $senior->fresh()->address);
        $this->assertSame($senior->full_name, $first->requester_name);
        $this->assertSame('Synthetic street, Synthetic origin, Santa Maria, Bulacan', $first->fresh()->previous_location);
        $this->assertDatabaseCount('senior_citizen_transfers', 2);
        $response = $this->get(route('senior-citizens.show', [$senior, 'section' => 'transfers']))->assertSee('Transfer history');
        $this->assertSame(1, substr_count($response->getContent(), 'id="transfers"'));
        $this->assertSame(1, substr_count($response->getContent(), 'action="'.route('senior-citizens.transfers.store', $senior).'"'));
    }

    public function test_transfer_rejects_missing_destination_requester_and_future_date(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();

        $this->post(route('senior-citizens.transfers.store', $senior), ['type' => 'EXTERNAL', 'requested_by' => 'OTHER', 'transferred_on' => today()->addDay()->toDateString()])
            ->assertSessionHasErrors(['requester_name', 'requester_relationship', 'destination_address', 'destination_barangay', 'destination_municipality', 'destination_province', 'transferred_on']);

        $this->assertDatabaseCount('senior_citizen_transfers', 0);
        $this->assertSame('VERIFIED', $senior->fresh()->status->value);
    }

    public function test_self_requested_transfer_preserves_long_valid_full_names(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior(['first_name' => str_repeat('A', 100), 'middle_name' => str_repeat('B', 100), 'last_name' => str_repeat('C', 100)]);

        $this->post(route('senior-citizens.transfers.store', $senior), [...$this->externalTransfer(), 'requested_by' => 'SELF'])->assertSessionHasNoErrors();

        $this->assertSame($senior->full_name, $senior->transfers()->sole()->requester_name);
    }

    public function test_admin_cannot_record_transfers_and_guest_requires_login(): void
    {
        $senior = $this->senior();
        $this->post(route('senior-citizens.transfers.store', $senior), $this->externalTransfer())->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create());
        $this->post(route('senior-citizens.transfers.store', $senior), $this->externalTransfer())->assertForbidden();
        $this->assertDatabaseCount('senior_citizen_transfers', 0);
    }

    public function test_transfer_history_escapes_requester_and_destination_content(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        $unsafe = '<script>synthetic</script>';

        $this->post(route('senior-citizens.transfers.store', $senior), [...$this->externalTransfer(), 'requester_name' => $unsafe, 'destination_address' => $unsafe])->assertSessionHasNoErrors();

        $this->get(route('senior-citizens.show', [$senior, 'section' => 'transfers']))->assertSee($unsafe)->assertDontSee($unsafe, false);
    }

    public function test_internal_transfers_cannot_generate_a_cancellation_certificate(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        $this->post(route('senior-citizens.transfers.store', $senior), [
            'type' => 'INTERNAL', 'requested_by' => 'SELF', 'barangay_id' => $senior->barangay_id,
            'destination_address' => 'Synthetic next street', 'transferred_on' => today()->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->post(route('senior-citizens.transfers.certificate', [$senior, $senior->transfers()->sole()->id]))->assertStatus(422);

        $this->assertDatabaseMissing('audit_logs', ['action' => 'senior_citizen.cancellation_certificate_generated']);
    }

    public function test_external_transfer_cannot_target_santa_maria_bulacan(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();

        $this->post(route('senior-citizens.transfers.store', $senior), [...$this->externalTransfer(),
            'destination_municipality' => 'Santa Maria', 'destination_province' => 'Bulacan',
        ])->assertSessionHasErrors('destination_municipality');

        $this->assertSame('VERIFIED', $senior->fresh()->status->value);
        $this->assertDatabaseCount('senior_citizen_transfers', 0);
    }

    public function test_transferred_record_cannot_be_archived_to_revalidate_its_id(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        $credential = app(IdCredential::class)->createForIssued($senior);
        $this->post(route('senior-citizens.transfers.store', $senior), $this->externalTransfer())->assertSessionHasNoErrors();
        $this->flushSession();
        $this->actingAs(User::factory()->admin()->create());

        $this->delete(route('administration.senior-citizens.destroy', $senior))->assertForbidden();

        $this->assertSame('TRANSFERRED', $senior->fresh()->status->value);
        $this->assertFalse(app(IdCredential::class)->valid($credential->fresh()));
    }

    public function test_correcting_a_death_declaration_preserves_external_cancellation(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        $this->post(route('senior-citizens.transfers.store', $senior), $this->externalTransfer())->assertSessionHasNoErrors();
        $senior->update(['status' => 'DECEASED', 'died_on' => today(), 'death_declared_at' => now()]);
        $this->flushSession();
        $this->actingAs(User::factory()->admin()->create());

        $this->patch(route('senior-citizens.death.correct', $senior), ['reason' => 'Synthetic mistaken declaration'])->assertSessionHasNoErrors();

        $this->assertSame('TRANSFERRED', $senior->fresh()->status->value);
        $this->assertNull($senior->fresh()->died_on);
        $this->assertDatabaseHas('audit_logs', ['action' => 'senior_citizen.deceased_corrected', 'auditable_id' => $senior->id]);
    }

    public function test_transfer_dates_cannot_reverse_existing_history(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1));
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        $this->travel(2)->days();
        $input = ['type' => 'INTERNAL', 'requested_by' => 'SELF', 'barangay_id' => $senior->barangay_id, 'destination_address' => 'Synthetic next street', 'transferred_on' => '2026-10-03'];
        $this->post(route('senior-citizens.transfers.store', $senior), $input)->assertSessionHasNoErrors();

        $this->post(route('senior-citizens.transfers.store', $senior), [...$input, 'transferred_on' => '2026-10-02'])->assertSessionHasErrors('transferred_on');

        $this->assertDatabaseCount('senior_citizen_transfers', 1);
    }

    public function test_legacy_enrollment_cannot_bypass_staff_role_or_id_issuance(): void
    {
        $senior = $this->senior(['id_generated_at' => null]);
        $program = $this->program();
        $input = ['program_id' => $program->id, 'senior_citizen_id' => $senior->id, 'enrolled_on' => today()->toDateString()];
        $this->actingAs(User::factory()->admin()->create());
        $this->post(route('programs.beneficiaries.store', $program), $input)->assertForbidden();
        $this->flushSession();
        $this->actingAs(User::factory()->create());

        $this->post(route('programs.beneficiaries.store', $program), $input)->assertSessionHasErrors('senior_citizen_id');

        $this->assertDatabaseCount('beneficiaries', 0);
    }

    public function test_certificate_is_scoped_to_external_transfer_and_preserves_snapshot(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $this->officials();
        $senior = $this->senior();
        $this->post(route('senior-citizens.transfers.store', $senior), $this->externalTransfer())->assertSessionHasNoErrors();
        $transfer = $senior->transfers()->sole();
        $senior->update(['first_name' => 'Changed']);

        $response = $this->post(route('senior-citizens.transfers.certificate', [$senior, $transfer->id]))->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('Cache-Control', 'no-store, private');

        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertSame('Synthetic Applicant', $transfer->fresh()->senior_name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'senior_citizen.cancellation_certificate_generated', 'auditable_id' => $senior->id]);
        $other = $this->senior();
        $this->post(route('senior-citizens.transfers.certificate', [$other, $transfer->id]))->assertNotFound();
        $this->flushSession();
        $this->actingAs(User::factory()->admin()->create());
        $this->post(route('senior-citizens.transfers.certificate', [$senior, $transfer->id]))->assertForbidden();
    }

    #[TestWith(['short'])]
    #[TestWith(['long'])]
    #[TestWith(['a4'])]
    public function test_four_complete_ids_fit_on_one_sheet_at_original_dimensions(string $paper): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $this->officials();
        $cards = [];
        $ids = [];
        for ($i = 0; $i < 4; $i++) {
            $senior = $this->senior();
            $ids[] = $senior->id;
            app(IdCredential::class)->createForIssued($senior);
            $cards[] = app(IdCardLayout::class)->data($senior);
        }
        $options = new Options;
        $options->setTempDir(storage_path('framework/cache'));
        $options->setFontCache(storage_path('framework/cache'));
        $pdf = new Dompdf($options);
        [$width, $height] = IdCardLayout::PAPERS[$paper];
        $pdf->setPaper([0, 0, $width * 72 / 25.4, $height * 72 / 25.4]);
        $pdf->loadHtml(view('senior-citizens.ids.pdf', compact('cards'))->render());
        $sides = 0;
        $photos = 0;
        $pdf->setCallbacks([['event' => 'end_frame', 'f' => function ($frame) use (&$sides, &$photos): void {
            $node = $frame->get_node();
            if (! $node instanceof \DOMElement) {
                return;
            }
            $box = $frame->get_border_box();
            if ($node->getAttribute('class') === 'id-card') {
                $sides++;
                $this->assertEqualsWithDelta(85.6 * 72 / 25.4, $box['w'], 0.02);
                $this->assertEqualsWithDelta(53.98 * 72 / 25.4, $box['h'], 0.02);
            }
            if ($node->getAttribute('class') === 'id-photo') {
                $photos++;
                $this->assertEqualsWithDelta(72, $box['w'], 0.02);
                $this->assertEqualsWithDelta(72, $box['h'], 0.02);
            }
        }]]);

        $pdf->render();

        $this->assertSame(8, $sides);
        $this->assertSame(4, $photos);
        $this->assertSame(1, $pdf->getCanvas()->get_page_count());
        $this->post(route('senior-id.batch-print'), ['senior_ids' => $ids, 'paper' => $paper, 'purpose' => 'FIRST_PRINT'])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame(4, AuditLog::where('action', 'senior_citizen.id_print_generated')->count());
    }

    public function test_batch_print_rejects_unissued_ids_and_duplicate_selection(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior(['id_generated_at' => null]);
        $input = ['senior_ids' => [$senior->id], 'paper' => 'a4', 'purpose' => 'FIRST_PRINT'];

        $this->post(route('senior-id.batch-print'), $input)->assertStatus(422);
        $this->post(route('senior-id.batch-print'), [...$input, 'senior_ids' => [$senior->id, $senior->id]])->assertSessionHasErrors('senior_ids.0');
        $this->post(route('senior-id.batch-print'), [...$input, 'senior_ids' => array_fill(0, 5, $senior->id)])->assertSessionHasErrors('senior_ids');
        $this->assertDatabaseMissing('audit_logs', ['action' => 'senior_citizen.id_print_generated']);
    }

    public function test_admin_cannot_batch_print_and_inactive_program_cannot_be_assigned(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $senior = $this->senior();
        $this->post(route('senior-id.batch-print'), ['senior_ids' => [$senior->id], 'paper' => 'a4', 'purpose' => 'REPRINT'])->assertForbidden();
        $this->flushSession();
        $this->actingAs(User::factory()->create());
        $program = $this->program();
        $program->update(['status' => 'UPCOMING']);

        Livewire::test(SeniorRecord::class, ['seniorId' => $senior->id])->set('programId', (string) $program->id)->call('assignBenefit')->assertStatus(422);

        $this->assertDatabaseCount('beneficiaries', 0);
    }

    private function senior(array $overrides = []): SeniorCitizen
    {
        foreach (['municipality' => 'Santa Maria', 'province' => 'Bulacan', 'office_name' => 'Synthetic office', 'organization_name' => 'Municipality of Santa Maria'] as $key => $value) {
            if (! SystemConfiguration::query()->where('key', $key)->exists()) {
                SystemConfiguration::forceCreate(['key' => $key, 'value' => $value]);
            }
        }

        $barangay = Barangay::firstOrCreate(['code' => 'SYN-ORIGIN'], ['name' => 'Synthetic origin']);
        $number = SeniorCitizen::count();

        return SeniorCitizen::unguarded(fn () => SeniorCitizen::create([
            'barangay_id' => $barangay->id, 'registration_number' => 'SYN-CONSULT-'.$number,
            'first_name' => 'Synthetic', 'last_name' => 'Applicant', 'birth_date' => '1950-01-01',
            'sex' => 'FEMALE', 'address' => 'Synthetic street', 'contact_number' => '+639170000001',
            'status' => 'VERIFIED', 'verified_at' => now(), 'id_generated_at' => now(), 'osca_id_number' => 'SYN-ID-'.$number,
            ...$overrides,
        ]));
    }

    private function program(string $name = 'Synthetic benefit'): Program
    {
        return Program::create(['name' => $name, 'agency' => 'Synthetic agency', 'status' => 'ACTIVE']);
    }

    private function registration(SeniorCitizen $senior): array
    {
        return [
            ...$senior->only(['barangay_id', 'first_name', 'middle_name', 'last_name', 'address', 'sex', 'contact_number']),
            'birth_date' => $senior->birth_date->toDateString(), 'requirements_status' => 'REVIEW',
            'requirements_checks' => ['identity' => false, 'residency' => false, 'authorization' => false], 'review_reason' => 'Synthetic document review',
        ];
    }

    private function externalTransfer(): array
    {
        return ['type' => 'EXTERNAL', 'requested_by' => 'OTHER', 'requester_name' => 'Synthetic Requester', 'requester_relationship' => 'Child',
            'destination_municipality' => 'Synthetic municipality', 'destination_province' => 'Synthetic province', 'destination_barangay' => 'Synthetic destination barangay',
            'destination_address' => 'Synthetic destination street', 'transferred_on' => today()->toDateString()];
    }

    private function officials(): void
    {
        app(IdOfficials::class)->save(['osca_head_name' => 'Synthetic Head', 'municipal_mayor_name' => 'Synthetic Mayor'], [
            'municipal_seal' => UploadedFile::fake()->createWithContent('seal.jpg', file_get_contents(base_path('tests/Fixtures/id-photo.jpg'))),
        ]);
    }
}
