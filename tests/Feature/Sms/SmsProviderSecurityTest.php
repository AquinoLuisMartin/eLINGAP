<?php

namespace Tests\Feature\Sms;

use App\Services\Sms\SmsGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class SmsProviderSecurityTest extends TestCase
{
    public function test_only_delivery_status_is_returned_for_audit_storage(): void
    {
        config(['services.sms.url' => 'https://sms.example.test', 'services.sms.token' => 'synthetic-token']);
        Http::preventStrayRequests();
        Http::fake(['https://sms.example.test/messages' => Http::response([
            'id' => 'synthetic-message', 'recipient' => 'private-provider-value', 'debug' => 'private-provider-value',
        ], 202)]);

        $result = app(SmsGateway::class)->send('+639000000000', 'Synthetic test message', 'synthetic-id');

        $this->assertSame(['provider_message_id' => 'synthetic-message', 'response' => ['status' => 202]], $result);
        Http::assertSentCount(1);
    }

    public function test_provider_error_details_are_not_exposed_or_retried_for_client_errors(): void
    {
        config(['services.sms.url' => 'https://sms.example.test', 'services.sms.token' => 'synthetic-token']);
        Http::preventStrayRequests();
        Http::fake(['https://sms.example.test/messages' => Http::response(['debug' => 'private-provider-value'], 401)]);

        try {
            app(SmsGateway::class)->send('+639000000000', 'Synthetic test message', 'synthetic-id');
            $this->fail('The provider request should fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('SMS provider delivery request failed.', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }

        Http::assertSentCount(1);
    }
}
