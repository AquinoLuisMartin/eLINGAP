<?php

namespace Tests\Feature\Sms;

use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsMessagePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_retains_full_message_and_recipient_timestamps_for_the_dialog(): void
    {
        $barangay = Barangay::create(['code' => 'SMS-PREVIEW', 'name' => 'Synthetic barangay']);
        $senior = SeniorCitizen::create([
            'barangay_id' => $barangay->id, 'first_name' => 'SYNTHETIC', 'last_name' => 'RECIPIENT',
            'birth_date' => '1950-01-01', 'sex' => 'FEMALE', 'address' => 'Synthetic address', 'status' => 'VERIFIED',
        ]);
        $body = str_repeat('Synthetic message details. ', 30)."\nPlease bring your ID.";
        SmsMessage::create([
            'senior_citizen_id' => $senior->id, 'recipient_number' => '09XX XXX 0001', 'message' => $body,
            'status' => 'SENT', 'queued_at' => '2026-11-05 08:00:00', 'sent_at' => '2026-11-05 08:01:00',
        ]);

        $response = $this->actingAs(User::factory()->create())->get(route('sms.messages.index'));

        $document = new \DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $buttons = $xpath->query('//button[@data-message]');
        $this->assertCount(1, $buttons);
        $this->assertSame([
            'id' => SmsMessage::sole()->id,
            'recipient' => 'SYNTHETIC RECIPIENT', 'mobile' => '09XX XXX 0001', 'body' => $body,
            'queued' => '2026-11-05 08:00', 'sent' => '2026-11-05 08:01', 'status' => 'Sent',
            'failureReason' => null,
        ], json_decode($buttons->item(0)->getAttribute('data-message'), true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame('sms-message-dialog', $buttons->item(0)->getAttribute('aria-controls'));
        $this->assertCount(1, $xpath->query('//dialog[@id="sms-message-dialog"]'));
        $response->assertSee('Full message')->assertSee('Close');
    }

    public function test_unlinked_queued_message_uses_number_and_missing_timestamp_labels(): void
    {
        SmsMessage::create(['recipient_number' => '09XX XXX 0002', 'message' => 'Synthetic queued notice', 'status' => 'QUEUED']);

        $response = $this->actingAs(User::factory()->create())->get(route('sms.messages.index'));

        $document = new \DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $details = json_decode($xpath->query('//button[@data-message]')->item(0)->getAttribute('data-message'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('09XX XXX 0002', $details['recipient']);
        $this->assertSame('Not queued', $details['queued']);
        $this->assertSame('Not sent', $details['sent']);
        $this->assertSame('Queued', $details['status']);
    }

    public function test_message_and_failure_reason_are_escaped_and_failed_retry_remains_available(): void
    {
        $body = '</button><script>alert("synthetic")</script> "quoted" & text';
        $reason = '<img src=x onerror=alert("synthetic")>';
        $message = SmsMessage::create([
            'recipient_number' => '09XX XXX 0003', 'message' => $body, 'status' => 'FAILED', 'failure_reason' => $reason,
        ]);

        $response = $this->actingAs(User::factory()->create())->get(route('sms.messages.index'));

        $response->assertSee($body)->assertDontSee($body, false)
            ->assertDontSee($reason, false)
            ->assertSee('Confirm Retry')->assertDontSee('action="'.route('sms.messages.retry', $message).'"', false);
        $document = new \DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $details = json_decode($xpath->query('//button[@data-message]')->item(0)->getAttribute('data-message'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($reason, $details['failureReason']);
        $this->assertCount(5, $xpath->query('//table/thead/tr/th'));
        $this->assertCount(0, $xpath->query('//table/thead/tr/th[contains(., "Failure")]'));
        $this->assertCount(1, $xpath->query('//button[@data-retry]'));
    }

    public function test_individual_message_content_requires_authentication(): void
    {
        $this->get(route('sms.messages.index'))->assertRedirect(route('login'));
    }
}
