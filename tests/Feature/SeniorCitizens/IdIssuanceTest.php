<?php

namespace Tests\Feature\SeniorCitizens;

use App\Livewire\SeniorCitizens\IdManager;
use App\Livewire\SeniorCitizens\IdOfficialSettings;
use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\SeniorCitizenIdCredential;
use App\Models\User;
use App\Services\SeniorCitizens\IdAssets;
use App\Services\SeniorCitizens\IdCardLayout;
use App\Services\SeniorCitizens\IdCredential;
use App\Services\SeniorCitizens\IdOfficials;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class IdIssuanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_issuance_requires_review_without_a_digital_photo(): void
    {
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 10, 13));
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        Livewire::test(IdManager::class, ['seniorId' => $senior->id])->call('next')->assertHasNoErrors()->assertSet('step', 2);
        $this->assertDatabaseCount('senior_citizen_id_credentials', 0);
        $this->assertNull($senior->fresh()->id_generated_at);

        Livewire::test(IdManager::class, ['seniorId' => $senior->id])->call('next')->assertSet('step', 2)->call('issue')->assertHasErrors('reviewed')
            ->set('reviewed', true)->call('issue')->assertHasNoErrors()->assertSet('step', 3);
        $this->assertNotNull($senior->fresh()->id_generated_at);
        $this->assertMatchesRegularExpression('/^26-\d{4,}$/', $senior->fresh()->osca_id_number);
        $this->assertDatabaseHas('audit_logs', ['action' => 'senior_citizen.id_generated', 'auditable_id' => $senior->id]);
    }

    public function test_pending_registration_cannot_issue_an_id_even_with_a_photo(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $senior = $this->senior(['status' => 'PENDING', 'verified_at' => null]);
        $this->photo($senior);
        Livewire::test(IdManager::class, ['seniorId' => $senior->id])->call('next')->set('reviewed', true)->call('issue')->assertStatus(422);
        $this->assertDatabaseCount('senior_citizen_id_credentials', 0);
    }

    public function test_admin_monitors_but_cannot_manage_or_print_ids(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $senior = $this->senior();
        $this->assertTrue($admin->can('view', $senior));
        $this->assertFalse($admin->can('manageId', $senior));
        $this->assertFalse($admin->can('configureId', SeniorCitizen::class));
        $this->get(route('senior-id.show', $senior))->assertForbidden();
        $this->get(route('senior-id.preview', $senior))->assertForbidden();
        $this->post(route('senior-id.print', $senior), ['paper' => 'short', 'purpose' => 'REPRINT'])->assertForbidden();
        $this->get(route('senior-citizens.id-card', $senior))->assertForbidden();
        $this->get(route('senior-id.officials'))->assertForbidden();
        Livewire::test(IdManager::class, ['seniorId' => $senior->id])->assertForbidden();
        Livewire::test(IdOfficialSettings::class)->assertForbidden();
    }

    public function test_staff_pages_require_login(): void
    {
        $senior = $this->senior();
        $this->get(route('senior-id.show', $senior))->assertRedirect(route('login'));
        $this->get(route('senior-id.officials'))->assertRedirect(route('login'));
    }

    public function test_staff_cannot_upload_a_senior_photo_through_profile_editing(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        $this->put(route('senior-citizens.update', $senior), [
            ...$senior->only(['barangay_id', 'first_name', 'last_name', 'sex', 'address', 'contact_number']),
            'birth_date' => $senior->birth_date->toDateString(), 'photo' => $this->image(),
        ])->assertSessionHasErrors('photo');
        $this->assertDatabaseCount('senior_citizen_documents', 0);
    }

    public function test_admin_cannot_bypass_id_photo_permissions_through_profile_editing(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->admin()->create());
        $senior = $this->senior();
        $this->put(route('senior-citizens.update', $senior), [
            ...$senior->only(['barangay_id', 'first_name', 'last_name', 'sex', 'address', 'contact_number']),
            'birth_date' => $senior->birth_date->toDateString(), 'photo' => $this->image(),
        ])->assertSessionHasErrors('photo');
        $this->assertDatabaseCount('senior_citizen_documents', 0);
    }

    public function test_legacy_intake_photo_does_not_block_physical_photo_preparation(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        $path = UploadedFile::fake()->createWithContent('portrait.jpg', file_get_contents(base_path('tests/Fixtures/id-photo-portrait.jpg')))->store('senior-photos', 'local');
        $senior->documents()->create(['document_type' => 'PHOTO', 'path' => $path, 'original_name' => 'portrait.jpg', 'mime_type' => 'image/jpeg', 'size' => Storage::disk('local')->size($path)]);
        Livewire::test(IdManager::class, ['seniorId' => $senior->id])->call('next')->assertHasNoErrors()->assertSet('step', 2);
        $this->assertNull($senior->fresh()->id_generated_at);
    }

    public function test_preview_does_not_issue_an_id_or_allocate_a_number(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        $this->get(route('senior-id.preview', $senior))->assertOk()->assertSee('DRAFT - NOT ISSUED')->assertSee('Thumbprint')->assertSee('2.54 x 2.54 cm')->assertDontSee('Printed Name and Signature');
        $this->assertDatabaseCount('osca_id_numbers', 0);
        $this->assertDatabaseCount('senior_citizen_id_credentials', 0);
        $this->assertNull($senior->fresh()->id_generated_at);
    }

    public function test_existing_issued_id_receives_one_permanent_encrypted_credential(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior(['osca_id_number' => '26-0031', 'id_generated_at' => '2026-03-01 09:00:00']);
        $service = app(IdCredential::class);
        $first = $service->createForIssued($senior);
        $second = $service->createForIssued($senior);
        $this->assertSame($first->verification_token, $second->verification_token);
        $this->assertSame($first->id, $second->id);
        $this->assertNotSame($first->verification_token, DB::table('senior_citizen_id_credentials')->value('verification_token'));
        $this->assertArrayNotHasKey('verification_token', $first->toArray());
        $this->assertArrayNotHasKey('verification_token_hash', $first->toArray());
        $this->assertSame('26-0031', $senior->fresh()->osca_id_number);
        $this->assertSame('2026-03-01', $senior->fresh()->id_generated_at->toDateString());
        $this->assertDatabaseCount('osca_id_numbers', 0);
        $this->assertDatabaseCount('senior_citizen_id_credentials', 1);
    }

    public function test_different_seniors_receive_distinct_permanent_qr_codes(): void
    {
        config(['app.url' => 'https://example.test']);
        $this->actingAs(User::factory()->create());
        $firstSenior = $this->senior(['osca_id_number' => '26-0031', 'id_generated_at' => now()]);
        $secondSenior = $this->senior(['osca_id_number' => '26-0032', 'id_generated_at' => now()]);
        $service = app(IdCredential::class);

        $first = $service->createForIssued($firstSenior);
        $second = $service->createForIssued($secondSenior);
        $svg = $service->svg($first);

        $this->assertNotSame($first->verification_token_hash, $second->verification_token_hash);
        $this->assertNotSame($svg, $service->svg($second));
        $this->assertSame($svg, $service->svg($service->createForIssued($firstSenior)));
        $this->assertStringContainsString('<svg', $svg);
        $this->assertDatabaseCount('senior_citizen_id_credentials', 2);
    }

    #[TestWith(['senior_citizen_id'])]
    #[TestWith(['verification_token_hash'])]
    public function test_database_rejects_duplicate_qr_credentials(string $column): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior(['osca_id_number' => '26-0031', 'id_generated_at' => now()]);
        $credential = app(IdCredential::class)->createForIssued($senior);
        $duplicate = [
            'senior_citizen_id' => $senior->id,
            'verification_token_hash' => $credential->verification_token_hash,
            'verification_token' => $credential->getRawOriginal('verification_token'),
        ];
        if ($column === 'senior_citizen_id') {
            $duplicate['verification_token_hash'] = hash('sha256', 'synthetic-duplicate-credential');
        } else {
            $duplicate['senior_citizen_id'] = $this->senior()->id;
        }

        $this->expectException(UniqueConstraintViolationException::class);

        DB::transaction(fn () => DB::table('senior_citizen_id_credentials')->insert($duplicate));
    }

    public function test_public_verification_exposes_no_personal_information_and_archiving_does_not_invalidate(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior(['osca_id_number' => '26-0088', 'id_generated_at' => now()]);
        $credential = app(IdCredential::class)->createForIssued($senior);
        $this->app['auth']->forgetGuards();
        $url = route('id.verify', ['token' => $credential->verification_token]);
        $response = $this->get($url)->assertOk()->assertSee('Verified')->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer');
        foreach ([$senior->full_name, $senior->address, $senior->contact_number, $senior->osca_id_number, $senior->birth_date->format('m/d/Y'), $credential->verification_token] as $privateValue) {
            $response->assertDontSee($privateValue);
        }
        $senior->update(['status' => 'ARCHIVED']);
        $this->get($url)->assertSee('Verified');
        $senior->update(['status' => 'DECEASED', 'died_on' => today()]);
        $this->get($url)->assertSee('Not valid')->assertDontSee('deceased');
        $senior->update(['status' => 'PENDING', 'died_on' => null, 'verified_at' => null]);
        $this->get($url)->assertSee('Not valid');
        $senior->update(['status' => 'VERIFIED', 'verified_at' => now()]);
        $this->get($url)->assertSee('Verified');
    }

    public function test_unknown_qr_has_a_generic_result(): void
    {
        $this->get(route('id.verify', ['token' => str_repeat('a', 64)]))->assertOk()->assertSee('Not valid')->assertDontSee('Verified');
        $this->get(route('id.verify', ['token' => 'malformed']))->assertOk()->assertSee('Not valid');
    }

    public function test_archived_issued_ids_can_be_prepared_without_resetting_registration(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $senior = $this->senior(['status' => 'ARCHIVED', 'osca_id_number' => '26-0031', 'id_generated_at' => '2026-03-01']);
        Livewire::test(IdManager::class, ['seniorId' => $senior->id])
            ->call('next')->set('reviewed', true)->call('prepareExisting')->assertHasNoErrors()->assertSet('step', 3);
        $this->assertSame('ARCHIVED', $senior->fresh()->status->value);
        $this->assertSame('26-0031', $senior->fresh()->osca_id_number);
        $this->assertSame('2026-03-01', $senior->fresh()->id_generated_at->toDateString());
        $this->assertDatabaseCount('senior_citizen_id_credentials', 1);
    }

    public function test_signature_is_optional_and_wet_signature_preserves_private_history(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        $assets = app(IdAssets::class);
        $this->assertNull($assets->latest($senior, 'SIGNATURE'));
        $assets->save($senior, $this->image(), 'SIGNATURE');
        $path = $assets->latest($senior, 'SIGNATURE')->path;
        $assets->useWetSignature($senior);
        $this->assertNull($assets->latest($senior, 'SIGNATURE'));
        Storage::disk('local')->assertExists($path);
        $this->assertDatabaseHas('senior_citizen_documents', ['document_type' => 'SIGNATURE_HISTORY', 'senior_citizen_id' => $senior->id]);
    }

    public function test_id_preparation_uses_physical_photo_and_thumbprint_without_signature_pad(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();

        Livewire::test(IdManager::class, ['seniorId' => $senior->id])
            ->assertSee('Physical photo and thumbprint')->assertDontSee('Signature pad')->assertDontSee('Upload ID photo');

        $card = app(IdCardLayout::class)->data($senior, draft: true);
        $this->assertNull($card['photo']);
        $this->assertNull($card['signature']);
    }

    public function test_official_settings_are_staff_editable_and_audited(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        Livewire::test(IdOfficialSettings::class)->set('oscaHeadName', 'Synthetic Head')->set('mayorName', 'Synthetic Mayor')
            ->set('headSignature', $this->image())->set('seal', $this->image())->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('system_configurations', ['key' => 'osca_head_name', 'value' => 'Synthetic Head']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'senior_id.officials_updated']);
        $values = app(IdOfficials::class)->values();
        Storage::disk('local')->assertExists($values['municipal_seal']);
        Livewire::test(IdOfficialSettings::class)->set('remove', ['osca_head_signature'])->call('save')->assertHasNoErrors();
        $this->assertNull(app(IdOfficials::class)->values()['osca_head_signature']);
    }

    #[TestWith(['short', 792])]
    #[TestWith(['long', 936])]
    #[TestWith(['a4', 841.89])]
    public function test_print_document_matches_paper_and_retains_identity(string $paper, float $height): void
    {
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 10, 13));
        $this->actingAs(User::factory()->create());
        $this->configureOfficials();
        $senior = $this->senior(['birth_date' => '1960-10-12', 'osca_id_number' => '26-0031', 'id_generated_at' => '2026-03-01']);
        $this->photo($senior);
        $credential = app(IdCredential::class)->createForIssued($senior);
        $response = $this->post(route('senior-id.print', $senior), ['paper' => $paper, 'purpose' => 'REPLACEMENT'])
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        preg_match('/\/MediaBox\s*\[\s*0(?:\.\d+)?\s+0(?:\.\d+)?\s+([\d.]+)\s+([\d.]+)/', $response->getContent(), $box);
        $this->assertNotEmpty($box);
        $this->assertEqualsWithDelta($height, (float) $box[2], 0.02);
        $this->assertSame($credential->verification_token, SeniorCitizenIdCredential::first()->verification_token);
        $this->assertSame('2026-03-01', $senior->fresh()->id_generated_at->toDateString());
        $this->assertDatabaseHas('audit_logs', ['action' => 'senior_citizen.id_print_generated', 'auditable_id' => $senior->id]);
        $layout = app(IdCardLayout::class)->data($senior);
        $this->assertSame(66, $layout['age']);
        $this->travel(1)->years();
        $this->assertSame(67, app(IdCardLayout::class)->data($senior->fresh())['age']);
    }

    public function test_printing_blocks_missing_qr_and_deceased_records(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $senior = $this->senior(['osca_id_number' => '26-0031', 'id_generated_at' => now()]);
        $this->photo($senior);
        $payload = ['paper' => 'long', 'purpose' => 'REPRINT'];
        $this->post(route('senior-id.print', $senior), $payload)->assertStatus(422);
        app(IdCredential::class)->createForIssued($senior);
        $senior->update(['status' => 'DECEASED']);
        $this->post(route('senior-id.print', $senior), $payload)->assertStatus(422);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'senior_citizen.id_print_generated']);
    }

    public function test_printing_rejects_unsupported_paper_and_purpose(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        $this->post(route('senior-id.print', $senior), ['paper' => 'legal', 'purpose' => 'OTHER'])->assertSessionHasErrors(['paper', 'purpose']);
    }

    public function test_preview_uses_the_included_seal_without_an_uploaded_replacement(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();

        $card = app(IdCardLayout::class)->data($senior, draft: true);

        $this->assertSame(file_get_contents(public_path('images/lgu-seal.png')), base64_decode(explode(',', $card['seal'], 2)[1]));
        Livewire::test(IdOfficialSettings::class)->assertSee('Using the included Santa Maria LGU seal.');
    }

    public function test_removing_an_uploaded_seal_restores_the_included_seal(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $this->configureOfficials();
        $senior = $this->senior();
        $card = app(IdCardLayout::class)->data($senior, draft: true);
        $this->assertSame(file_get_contents(base_path('tests/Fixtures/id-photo.jpg')), base64_decode(explode(',', $card['seal'], 2)[1]));

        Livewire::test(IdOfficialSettings::class)->set('remove', ['municipal_seal'])->call('save')->assertHasNoErrors()
            ->assertSee('Using the included Santa Maria LGU seal.');

        $card = app(IdCardLayout::class)->data($senior, draft: true);
        $this->assertSame(file_get_contents(public_path('images/lgu-seal.png')), base64_decode(explode(',', $card['seal'], 2)[1]));
    }

    public function test_printing_requires_official_names(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $senior = $this->senior(['osca_id_number' => '26-0031', 'id_generated_at' => now()]);
        $this->photo($senior);
        app(IdCredential::class)->createForIssued($senior);
        $this->post(route('senior-id.print', $senior), ['paper' => 'a4', 'purpose' => 'FIRST_PRINT'])->assertSessionHasErrors('officials');
        $this->assertDatabaseMissing('audit_logs', ['action' => 'senior_citizen.id_print_generated']);
    }

    public function test_printing_uses_the_included_seal_without_an_uploaded_replacement(): void
    {
        if (! function_exists('imagecreatefrompng')) {
            $this->markTestSkipped('PHP GD is required to print the included PNG seal.');
        }

        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        app(IdOfficials::class)->save(['osca_head_name' => 'Synthetic Head', 'municipal_mayor_name' => 'Synthetic Mayor'], []);
        $senior = $this->senior(['osca_id_number' => '26-0031', 'id_generated_at' => now()]);
        $this->photo($senior);
        app(IdCredential::class)->createForIssued($senior);

        $response = $this->post(route('senior-id.print', $senior), ['paper' => 'a4', 'purpose' => 'FIRST_PRINT']);

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertDatabaseHas('audit_logs', ['action' => 'senior_citizen.id_print_generated', 'auditable_id' => $senior->id]);
    }

    public function test_pdf_card_and_photo_dimensions_include_the_border(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $this->configureOfficials();
        $senior = $this->senior(['osca_id_number' => '26-0031', 'id_generated_at' => now()]);
        $this->photo($senior);
        app(IdCredential::class)->createForIssued($senior);
        $card = app(IdCardLayout::class)->data($senior);
        $options = new Options;
        $options->setTempDir(storage_path('framework/cache'));
        $options->setFontCache(storage_path('framework/cache'));
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('senior-citizens.ids.pdf', ['cards' => [$card]])->render());
        $pdf->setPaper('a4');
        $cards = 0;
        $photos = 0;
        $bounds = [];
        $pdf->setCallbacks([['event' => 'end_frame', 'f' => function ($frame) use (&$cards, &$photos, &$bounds): void {
            $node = $frame->get_node();
            if (! $node instanceof \DOMElement) {
                return;
            }
            $box = $frame->get_border_box();
            $bounds[$node->getAttribute('class')] = $box;
            if ($node->getAttribute('class') === 'id-card') {
                $cards++;
                $this->assertEqualsWithDelta(85.60 * 72 / 25.4, $box['w'], 0.02);
                $this->assertEqualsWithDelta(53.98 * 72 / 25.4, $box['h'], 0.02);
            }
            if ($node->getAttribute('class') === 'id-photo') {
                $photos++;
                $this->assertEqualsWithDelta(72, $box['w'], 0.02);
                $this->assertEqualsWithDelta(72, $box['h'], 0.02);
            }
        }]]);
        $pdf->render();
        $this->assertSame(2, $cards);
        $this->assertSame(1, $photos);
        $this->assertSame(1, $pdf->getCanvas()->get_page_count());
        $this->assertLessThanOrEqual($bounds['id-qr']['y'], $bounds['id-benefits']['y'] + $bounds['id-benefits']['h']);
        $this->assertLessThanOrEqual($bounds['id-officials']['y'], $bounds['id-warning']['y'] + $bounds['id-warning']['h']);
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('synthetic.jpg', file_get_contents(base_path('tests/Fixtures/id-photo.jpg')));
    }

    private function configureOfficials(): void
    {
        app(IdOfficials::class)->save(['osca_head_name' => 'Synthetic Head', 'municipal_mayor_name' => 'Synthetic Mayor'], ['municipal_seal' => $this->image()]);
    }

    private function photo(SeniorCitizen $senior): void
    {
        app(IdAssets::class)->save($senior, $this->image(), 'PHOTO');
    }

    private function senior(array $overrides = []): SeniorCitizen
    {
        $barangay = Barangay::create(['name' => 'Synthetic Barangay '.Barangay::count(), 'code' => 'SYN-'.Barangay::count()]);

        return SeniorCitizen::unguarded(fn () => SeniorCitizen::create([
            'barangay_id' => $barangay->id, 'registration_number' => 'SYN-ID-'.SeniorCitizen::count(),
            'first_name' => 'Synthetic', 'last_name' => 'Applicant', 'birth_date' => '1960-01-01',
            'sex' => 'FEMALE', 'address' => 'Synthetic street', 'contact_number' => '+639170000001',
            'status' => 'VERIFIED', 'verified_at' => '2026-01-01', ...$overrides,
        ]));
    }
}
