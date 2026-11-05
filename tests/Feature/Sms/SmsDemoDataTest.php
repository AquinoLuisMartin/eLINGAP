<?php

namespace Tests\Feature\Sms;

use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\SmsBlast;
use App\Models\SmsTemplate;
use App\Models\User;
use Database\Seeders\BenefitProgramSeeder;
use Database\Seeders\ProgramSmsTemplateSeeder;
use Database\Seeders\SampleSmsBroadcastSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class SmsDemoDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_templates_are_visible_and_rerunning_preserves_saved_text(): void
    {
        $this->seed(BenefitProgramSeeder::class);
        Program::create(['name' => 'Inactive sample program', 'agency' => 'OSCA', 'status' => 'COMPLETED']);

        $this->seed(ProgramSmsTemplateSeeder::class);

        $this->assertDatabaseCount('sms_templates', 18);
        $this->assertDatabaseMissing('sms_templates', ['name' => 'Inactive sample program - Iskedyul']);
        $template = SmsTemplate::where('name', 'T.S.S. - Tulong sa Senior - Mga kinakailangan')->sole();
        $this->assertStringContainsString('pagsusuri ng inyong kahilingan', $template->body);
        $template->update(['body' => 'Office-approved custom wording.', 'is_active' => false]);

        $this->seed(ProgramSmsTemplateSeeder::class);

        $this->assertDatabaseCount('sms_templates', 18);
        $this->assertDatabaseHas('sms_templates', ['id' => $template->id, 'body' => 'Office-approved custom wording.', 'is_active' => false]);
        $this->actingAs(User::factory()->create())->get(route('sms.templates.index'))
            ->assertSee('Office-approved custom wording.')->assertSee('Iskedyul');
    }

    public function test_broadcast_history_matches_synthetic_program_beneficiaries_without_sending_sms(): void
    {
        $this->freezeTime();
        $staff = User::factory()->create();
        $program = Program::create(['name' => 'Synthetic assistance', 'agency' => 'OSCA', 'status' => 'ACTIVE']);
        $barangay = Barangay::create(['code' => 'MOCK-SMS', 'name' => 'Synthetic barangay']);
        foreach (['SYNTHETIC ADDRESS - NOT A RESIDENCE', 'Ordinary address'] as $address) {
            $senior = SeniorCitizen::create([
                'barangay_id' => $barangay->id, 'first_name' => 'SYNTHETIC', 'last_name' => 'BENEFICIARY',
                'birth_date' => '1950-01-01', 'sex' => 'FEMALE', 'address' => $address, 'status' => 'VERIFIED',
            ]);
            Beneficiary::create(['program_id' => $program->id, 'senior_citizen_id' => $senior->id, 'status' => 'ACTIVE', 'enrolled_on' => today()]);
        }
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake();

        $this->seed(SampleSmsBroadcastSeeder::class);
        $this->seed(SampleSmsBroadcastSeeder::class);

        $this->assertDatabaseCount('sms_blasts', 1);
        $this->assertDatabaseCount('sms_messages', 1);
        $this->assertDatabaseCount('sms_delivery_logs', 1);
        $blast = SmsBlast::sole();
        $this->assertSame(1, $blast->recipient_count);
        $this->assertSame($staff->id, $blast->created_by);
        $this->assertSame('SENT', $blast->messages()->sole()->status->value);
        $this->actingAs($staff)->get(route('sms.blasts.index'))
            ->assertSee('[MOCK BROADCAST]')->assertSee('Synthetic assistance')->assertDontSee('No broadcasts have been queued yet.');
        $this->get(route('sms.blasts.show', $blast))->assertSee('SYNTHETIC BENEFICIARY')->assertSee('Sent');
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_broadcast_seeder_without_staff_or_sample_beneficiaries_creates_no_history(): void
    {
        $this->seed(SampleSmsBroadcastSeeder::class);
        $this->assertDatabaseCount('sms_blasts', 0);

        User::factory()->create();
        $this->seed(SampleSmsBroadcastSeeder::class);
        $this->assertDatabaseCount('sms_blasts', 0);
        $this->assertDatabaseCount('sms_messages', 0);
    }

    public function test_sample_broadcasts_are_refused_in_production(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Sample SMS broadcasts are only available outside production.');

        app()->call([new SampleSmsBroadcastSeeder, 'run']);
    }
}
