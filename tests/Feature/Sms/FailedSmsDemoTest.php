<?php

namespace Tests\Feature\Sms;

use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\SmsBlast;
use App\Models\User;
use Database\Seeders\SampleFailedSmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FailedSmsDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_samples_show_required_columns_and_resend_actions_without_sending_sms(): void
    {
        $this->actingAs(User::factory()->create());
        $this->beneficiaries();
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake();

        $this->seed(SampleFailedSmsSeeder::class);
        $this->seed(SampleFailedSmsSeeder::class);

        $this->assertDatabaseCount('sms_messages', 4);
        $this->assertDatabaseCount('sms_delivery_logs', 8);
        $blast = SmsBlast::sole();
        $response = $this->get(route('sms.blasts.show', $blast));
        foreach (['OSCA ID', 'Senior Name', 'Mobile Number', 'Failure Reason', 'Status', 'Action',
            'Invalid number format', 'Carrier network timeout', 'Inbox full', 'SMS Gateway error',
            'MOCK-ID-1', 'SYNTHETIC BENEFICIARY', '09XX XXX 0001', 'Failed', 'Resend'] as $text) {
            $response->assertSee($text);
        }
        $this->assertSame(4, substr_count($response->getContent(), '>Resend</button>'));
        $this->assertSame(4, $blast->messages()->where('status', 'FAILED')->count());
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_mock_resend_records_success_and_cannot_be_submitted_twice_or_reset_by_seeding(): void
    {
        $this->freezeTime();
        $this->actingAs(User::factory()->create());
        $this->beneficiaries();
        $this->seed(SampleFailedSmsSeeder::class);
        $message = SmsBlast::sole()->messages()->firstOrFail();
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake();

        $this->post(route('sms.messages.retry', $message))->assertRedirect()
            ->assertSessionHas('status', 'Mock resend completed. No SMS was sent.');

        $this->assertDatabaseHas('sms_messages', ['id' => $message->id, 'status' => 'SENT', 'failure_reason' => null, 'sent_at' => now()]);
        $this->assertSame(3, $message->deliveryLogs()->count());
        $this->assertSame(false, $message->deliveryLogs()->latest('id')->first()->response_payload['sms_sent']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'sms.mock_resent', 'auditable_id' => $message->id]);
        $this->post(route('sms.messages.retry', $message))->assertConflict();
        $this->seed(SampleFailedSmsSeeder::class);
        $this->assertSame('SENT', $message->fresh()->status->value);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_mock_resend_requires_authentication(): void
    {
        User::factory()->create();
        $this->beneficiaries();
        $this->seed(SampleFailedSmsSeeder::class);
        $message = SmsBlast::sole()->messages()->firstOrFail();

        $this->post(route('sms.messages.retry', $message))->assertRedirect(route('login'));

        $this->assertSame('FAILED', $message->fresh()->status->value);
        $this->assertSame(2, $message->deliveryLogs()->count());
    }

    public function test_mock_resend_is_refused_in_production(): void
    {
        $this->actingAs(User::factory()->create());
        $this->beneficiaries();
        $this->seed(SampleFailedSmsSeeder::class);
        $message = SmsBlast::sole()->messages()->firstOrFail();
        $this->app->instance('env', 'production');
        Queue::fake();

        $this->withSession(['_token' => 'mock-csrf'])->post(route('sms.messages.retry', $message), ['_token' => 'mock-csrf'])->assertUnprocessable();

        $this->assertSame('FAILED', $message->fresh()->status->value);
        Queue::assertNothingPushed();
    }

    private function beneficiaries(): void
    {
        $barangay = Barangay::create(['code' => 'FAILED-DEMO', 'name' => 'Synthetic barangay']);
        $program = Program::create(['name' => 'Synthetic benefit', 'agency' => 'OSCA', 'status' => 'ACTIVE']);
        for ($index = 1; $index <= 4; $index++) {
            $senior = SeniorCitizen::create([
                'barangay_id' => $barangay->id, 'first_name' => 'SYNTHETIC', 'last_name' => 'BENEFICIARY',
                'birth_date' => '1950-01-01', 'sex' => 'FEMALE', 'status' => 'VERIFIED',
                'address' => 'SYNTHETIC ADDRESS - NOT A RESIDENCE', 'osca_id_number' => 'MOCK-ID-'.$index,
            ]);
            Beneficiary::create(['program_id' => $program->id, 'senior_citizen_id' => $senior->id, 'status' => 'ACTIVE', 'enrolled_on' => today()]);
        }
    }
}
