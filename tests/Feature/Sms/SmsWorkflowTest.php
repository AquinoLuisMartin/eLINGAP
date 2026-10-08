<?php

namespace Tests\Feature\Sms;

use App\Jobs\Sms\SendSmsJob;
use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\Sms\SmsGateway;
use App\Services\Sms\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class SmsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_queueing_sms_persists_before_dispatching_the_job(): void
    {
        Queue::fake();

        $message = app(SmsService::class)->queue('09171234567', 'Paalala mula sa OSCA.');

        $this->assertSame('QUEUED', $message->fresh()->status->value);
        $this->assertDatabaseHas('sms_messages', ['id' => $message->id, 'status' => 'QUEUED']);
        Queue::assertPushed(SendSmsJob::class, fn (SendSmsJob $job) => $job->smsMessageId === $message->id);
    }

    public function test_sms_job_records_provider_success_and_delivery_history(): void
    {
        $message = SmsMessage::create([
            'recipient_number' => '09171234567',
            'message' => 'Paalala mula sa OSCA.',
            'status' => 'QUEUED',
            'queued_at' => now(),
        ]);

        $gateway = Mockery::mock(SmsGateway::class);
        $gateway->expects('send')->once()->andReturn(['provider_message_id' => 'provider-123', 'response' => ['status' => 'accepted']]);
        $this->app->instance(SmsGateway::class, $gateway);

        app()->call([new SendSmsJob($message->id), 'handle']);

        $this->assertSame('SENT', $message->fresh()->status->value);
        $this->assertDatabaseHas('sms_delivery_logs', ['sms_message_id' => $message->id, 'status' => 'SENT', 'provider_message_id' => 'provider-123']);
    }

    public function test_sms_job_refuses_a_recipient_who_became_ineligible(): void
    {
        $barangay = Barangay::create(['name' => 'Poblacion', 'code' => 'POB']);
        $senior = SeniorCitizen::create(['barangay_id' => $barangay->id, 'registration_number' => 'SC-TEST-001', 'first_name' => 'Maria', 'last_name' => 'Santos', 'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Poblacion', 'status' => 'DECEASED', 'contact_number' => '09171234567']);
        $message = SmsMessage::create(['senior_citizen_id' => $senior->id, 'recipient_number' => '+639171234567', 'message' => 'Reminder', 'status' => 'QUEUED']);
        $gateway = Mockery::mock(SmsGateway::class);
        $gateway->shouldNotReceive('send');
        $this->app->instance(SmsGateway::class, $gateway);

        app()->call([new SendSmsJob($message->id), 'handle']);

        $this->assertSame('FAILED', $message->fresh()->status->value);
        $this->assertDatabaseHas('sms_delivery_logs', ['sms_message_id' => $message->id, 'status' => 'FAILED']);
    }

    public function test_failed_message_retry_rechecks_eligibility(): void
    {
        Queue::fake();
        $staff = User::factory()->create();
        $message = SmsMessage::create(['recipient_number' => '09171234567', 'message' => 'Reminder', 'status' => 'FAILED']);

        $this->actingAs($staff)->post(route('sms.messages.retry', $message))->assertRedirect();
        $this->assertSame('QUEUED', $message->fresh()->status->value);
        Queue::assertPushed(SendSmsJob::class, fn (SendSmsJob $job) => $job->smsMessageId === $message->id);

        $this->post(route('sms.messages.retry', $message))->assertStatus(409);
    }
}
