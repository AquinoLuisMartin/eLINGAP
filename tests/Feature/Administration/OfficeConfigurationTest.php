<?php

namespace Tests\Feature\Administration;

use App\Livewire\Benefits\Programs;
use App\Livewire\Benefits\Workspace;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\BenefitSchedule;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\Benefits\BenefitSms;
use App\Services\SeniorCitizens\IdCardAddress;
use App\Services\SeniorCitizens\IdCardLayout;
use App\Services\SeniorCitizens\IdCredential;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class OfficeConfigurationTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['/'])]
    #[TestWith(['/login'])]
    public function test_public_pages_have_empty_states_instead_of_fixed_office_information(string $path): void
    {
        $this->get($path)->assertOk()->assertSee('Office not configured')
            ->assertSee('Address not configured')->assertSee('Contact number not configured')
            ->assertSee('Office hours not configured')->assertDontSee('Santa Maria')
            ->assertDontSee('+63 44 288 2604')->assertDontSee('google.com/maps/search')
            ->assertDontSee('Saturday &amp; Sunday', false);
    }

    public function test_public_heading_and_map_follow_stored_settings_on_each_request(): void
    {
        $this->settings(['office_name' => 'Synthetic office', 'organization_name' => 'Synthetic organization',
            'municipality' => 'Synthetic town', 'office_address' => 'Synthetic street & plaza']);

        $this->get('/')->assertOk()->assertSee('in Synthetic town')->assertSee('Synthetic organization')
            ->assertSee('query=Synthetic+street+%26+plaza', false);
        SystemConfiguration::query()->where('key', 'municipality')->update(['value' => 'Revised town']);
        $this->get('/')->assertSee('in Revised town')->assertDontSee('in Synthetic town');
    }

    public function test_missing_settings_table_keeps_login_available_without_fabricated_information(): void
    {
        DB::statement('ALTER TABLE system_configurations RENAME TO temporarily_unavailable_settings');
        Log::shouldReceive('warning')->once()->with('Office settings are unavailable.', ['failure_type' => QueryException::class]);

        $this->get('/login')->assertOk()->assertSee('Office not configured')->assertDontSee('+63 44 288 2604');
    }

    public function test_administrator_can_save_settings_and_only_actual_changes_are_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $input = ['settings' => ['office_name' => ' Synthetic office ', 'municipality' => 'Synthetic town', 'province' => 'Synthetic province']];

        $this->patch(route('administration.configuration.update'), $input)
            ->assertRedirectToRoute('administration.dashboard', ['tab' => 'configuration'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('system_configurations', ['key' => 'office_name', 'value' => 'Synthetic office', 'updated_by' => $admin->id]);
        $this->assertDatabaseCount('audit_logs', 3);
        $this->assertDatabaseHas('audit_logs', ['action' => 'system_configuration.updated', 'user_id' => $admin->id]);
        $this->patch(route('administration.configuration.update'), $input)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('audit_logs', 3);
    }

    public function test_administrator_settings_form_uses_saved_values_and_escapes_content(): void
    {
        $unsafe = '<script>alert("synthetic")</script>';
        $this->settings(['office_name' => $unsafe]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('administration.dashboard', ['tab' => 'configuration']))
            ->assertOk()->assertSee('Save office settings')->assertSee($unsafe)->assertDontSee($unsafe, false);
    }

    public function test_guest_cannot_save_office_settings(): void
    {
        $this->patch(route('administration.configuration.update'), ['settings' => ['office_name' => 'Synthetic office']])
            ->assertRedirectToRoute('login');

        $this->assertDatabaseCount('system_configurations', 0);
    }

    public function test_staff_cannot_save_office_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->patch(route('administration.configuration.update'), ['settings' => ['office_name' => 'Synthetic office']])->assertForbidden();

        $this->assertDatabaseCount('system_configurations', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_deactivated_administrator_cannot_save_settings(): void
    {
        $this->actingAs(User::factory()->admin()->inactive()->create())
            ->patch(route('administration.configuration.update'), ['settings' => ['office_name' => 'Synthetic office']])
            ->assertRedirectToRoute('login')->assertSessionHasErrors(['username' => 'This account has been deactivated.']);

        $this->assertDatabaseCount('system_configurations', 0);
    }

    public function test_missing_settings_payload_is_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create())->patch(route('administration.configuration.update'))
            ->assertSessionHasErrors('settings');

        $this->assertDatabaseCount('system_configurations', 0);
    }

    public function test_settings_changes_are_rolled_back_if_audit_recording_fails(): void
    {
        $this->settings(['office_name' => 'Original synthetic office']);
        $admin = User::factory()->admin()->create();
        Exceptions::fake();
        Event::listen('eloquent.creating: '.AuditLog::class, fn () => throw new RuntimeException('Synthetic audit failure'));

        $this->actingAs($admin)->patch(route('administration.configuration.update'),
            ['settings' => ['municipality' => 'Synthetic town', 'office_name' => 'Changed synthetic office']])->assertInternalServerError();

        $this->assertDatabaseHas('system_configurations', ['key' => 'office_name', 'value' => 'Original synthetic office']);
        $this->assertDatabaseMissing('system_configurations', ['key' => 'municipality']);
        $this->assertDatabaseCount('audit_logs', 0);
        Exceptions::assertReported(RuntimeException::class);
    }

    #[TestWith(['support_email', 'invalid-address'])]
    #[TestWith(['office_name', ['invalid']])]
    #[TestWith(['office_name', 123])]
    public function test_invalid_office_settings_are_not_saved(string $key, mixed $value): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('administration.configuration.update'), ['settings' => [$key => $value]])
            ->assertSessionHasErrors('settings.'.$key);

        $this->assertDatabaseCount('system_configurations', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    #[TestWith(['municipality', 181])]
    #[TestWith(['province', 181])]
    #[TestWith(['office_address', 1001])]
    public function test_oversized_settings_are_rejected(string $key, int $length): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('administration.configuration.update'), ['settings' => [$key => str_repeat('x', $length)]])
            ->assertSessionHasErrors('settings.'.$key);

        $this->assertDatabaseCount('system_configurations', 0);
    }

    public function test_unknown_and_operational_settings_cannot_be_written_through_the_office_form(): void
    {
        $this->settings(['sms_credits' => '5']);

        $this->actingAs(User::factory()->admin()->create())->patch(route('administration.configuration.update'),
            ['settings' => ['office_name' => 'Synthetic office', 'sms_credits' => '999']])->assertSessionHasErrors('settings');

        $this->assertDatabaseHas('system_configurations', ['key' => 'sms_credits', 'value' => '5']);
        $this->assertDatabaseMissing('system_configurations', ['key' => 'office_name']);
    }

    public function test_clearing_a_setting_does_not_restore_a_hardcoded_value(): void
    {
        $this->settings(['support_phone' => 'Synthetic contact']);
        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('administration.configuration.update'), ['settings' => ['support_phone' => '']])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('system_configurations', ['key' => 'support_phone', 'value' => null]);
        $this->get('/')->assertSee('Contact number not configured')->assertDontSee('Synthetic contact');
    }

    public function test_program_forms_use_the_stored_office_instead_of_a_fixed_agency(): void
    {
        $this->settings(['office_name' => 'Synthetic agency']);
        Livewire::actingAs(User::factory()->create())->test(Programs::class)->assertSet('form.agency', 'Synthetic agency');
        Livewire::test(Workspace::class)->assertSet('newProgram.agency', 'Synthetic agency');
    }

    public function test_program_forms_do_not_invent_an_agency_or_create_a_fixed_catalog_when_unconfigured(): void
    {
        Livewire::actingAs(User::factory()->create())->test(Programs::class)
            ->assertSet('form.agency', '')->assertSee('No program definitions recorded.')
            ->assertDontSee('Initialize approved programs');
        Livewire::test(Workspace::class)->assertSet('newProgram.agency', '');

        $this->assertDatabaseCount('programs', 0);
    }

    public function test_benefit_sms_uses_database_office_headings_and_the_schedule_snapshot(): void
    {
        $this->settings(['office_name' => 'Synthetic office', 'organization_name' => 'Synthetic organization']);
        $program = new Program(['name' => 'Current program']);
        $schedule = new BenefitSchedule(['scheduled_at' => '2026-10-26 10:00:00', 'location' => 'Synthetic venue',
            'rule_snapshot' => ['program_name' => 'Approved program'], 'allow_representative' => false]);
        $schedule->setRelation('program', $program);

        $text = app(BenefitSms::class)->template($schedule);

        $this->assertStringStartsWith("Synthetic office\nSynthetic organization\n\nProgram: Approved program", $text);
        $this->assertStringContainsString('Location: Synthetic venue', $text);
        $this->assertStringNotContainsString('Sta. Maria', $text);
    }

    public function test_internal_transfer_uses_database_locality_and_preserves_its_origin_snapshot(): void
    {
        $this->settings(['municipality' => 'Synthetic town', 'province' => 'Synthetic province']);
        $senior = $this->senior();
        $this->actingAs(User::factory()->create())->post(route('senior-citizens.transfers.store', $senior), $this->transfer($senior))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('senior_citizen_transfers', ['senior_citizen_id' => $senior->id,
            'destination_municipality' => 'Synthetic town', 'destination_province' => 'Synthetic province',
            'previous_location' => 'Synthetic street, Synthetic barangay, Synthetic town, Synthetic province']);
        SystemConfiguration::query()->where('key', 'municipality')->update(['value' => 'Revised town']);
        $this->assertSame('Synthetic street, Synthetic barangay, Synthetic town, Synthetic province', $senior->transfers()->sole()->previous_location);
    }

    public function test_external_transfer_cannot_target_the_configured_locality(): void
    {
        $this->settings(['municipality' => 'Synthetic town', 'province' => 'Synthetic province']);
        $senior = $this->senior();

        $this->actingAs(User::factory()->create())->post(route('senior-citizens.transfers.store', $senior), [
            ...$this->transfer($senior), 'type' => 'EXTERNAL', 'destination_municipality' => ' synthetic TOWN ',
            'destination_province' => 'SYNTHETIC province', 'destination_barangay' => 'Synthetic destination',
        ])->assertSessionHasErrors(['destination_municipality' => 'Use an internal transfer for a destination within the configured municipality and province.']);

        $this->assertDatabaseCount('senior_citizen_transfers', 0);
    }

    public function test_missing_locality_prevents_a_transfer_without_changing_the_senior_record(): void
    {
        $senior = $this->senior();
        $this->actingAs(User::factory()->create())->post(route('senior-citizens.transfers.store', $senior), $this->transfer($senior))
            ->assertSessionHasErrors('configuration');

        $this->assertDatabaseCount('senior_citizen_transfers', 0);
        $this->assertSame('Synthetic street', $senior->fresh()->address);
    }

    #[TestWith(['municipality'])]
    #[TestWith(['province'])]
    public function test_partially_configured_locality_cannot_record_a_transfer(string $key): void
    {
        $this->settings([$key => 'Synthetic locality']);
        $senior = $this->senior();

        $this->actingAs(User::factory()->create())->post(route('senior-citizens.transfers.store', $senior), $this->transfer($senior))
            ->assertSessionHasErrors('configuration');

        $this->assertDatabaseCount('senior_citizen_transfers', 0);
    }

    public function test_id_address_and_heading_use_stored_locality_and_office_values(): void
    {
        $this->settings(['municipality' => 'Synthetic town', 'province' => 'Synthetic province',
            'office_name' => 'Synthetic office', 'organization_name' => 'Synthetic municipality']);
        $senior = $this->senior();
        $card = app(IdCardLayout::class)->data($senior, draft: true);

        $this->assertSame('Synthetic street, Synthetic barangay, Synthetic town, Synthetic province', $card['address']['text']);
        $this->assertSame('Synthetic office', $card['office']['office_name']);
        $this->assertStringContainsString('SYNTHETIC MUNICIPALITY', view('components.senior-id.front', ['card' => $card])->render());
        $this->assertSame('Synthetic street, Synthetic barangay, Synthetic town, Synthetic province',
            app(IdCardAddress::class)->format('Synthetic street, Synthetic town, Synthetic province', 'Synthetic barangay')['text']);
    }

    public function test_missing_locality_prevents_printing_an_issued_id(): void
    {
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        app(IdCredential::class)->createForIssued($senior);

        $this->post(route('senior-id.print', $senior), ['paper' => 'a4', 'purpose' => 'REPRINT'])
            ->assertSessionHasErrors('configuration');

        $this->assertDatabaseMissing('audit_logs', ['action' => 'senior_citizen.id_print_generated']);
    }

    public function test_missing_office_names_prevent_printing_even_when_locality_is_configured(): void
    {
        $this->settings(['municipality' => 'Synthetic town', 'province' => 'Synthetic province']);
        $this->actingAs(User::factory()->create());
        $senior = $this->senior();
        app(IdCredential::class)->createForIssued($senior);

        $this->post(route('senior-id.print', $senior), ['paper' => 'a4', 'purpose' => 'REPRINT'])
            ->assertSessionHasErrors(['configuration' => 'Configure the office and organization names in System Configuration before printing official documents.']);

        $this->assertDatabaseMissing('audit_logs', ['action' => 'senior_citizen.id_print_generated']);
    }

    private function settings(array $values): void
    {
        foreach ($values as $key => $value) {
            SystemConfiguration::forceCreate(['key' => $key, 'value' => $value]);
        }
    }

    private function senior(): SeniorCitizen
    {
        $barangay = Barangay::create(['code' => 'SYN', 'name' => 'Synthetic barangay']);

        return SeniorCitizen::unguarded(fn () => SeniorCitizen::create(['barangay_id' => $barangay->id,
            'registration_number' => 'SYN-OFFICE-1', 'first_name' => 'Synthetic', 'last_name' => 'Applicant',
            'birth_date' => '1950-01-01', 'sex' => 'FEMALE', 'address' => 'Synthetic street',
            'status' => 'VERIFIED', 'verified_at' => now(), 'id_generated_at' => now(), 'osca_id_number' => 'SYN-OFFICE-ID']));
    }

    private function transfer(SeniorCitizen $senior): array
    {
        return ['type' => 'INTERNAL', 'requested_by' => 'SELF', 'barangay_id' => $senior->barangay_id,
            'destination_address' => 'Synthetic next street', 'transferred_on' => today()->toDateString()];
    }
}
