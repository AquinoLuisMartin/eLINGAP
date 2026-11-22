<?php

namespace Tests\Feature\Sms;

use App\Services\Sms\SmsGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class SmsProviderSecurityTest extends TestCase
{
    #[TestWith(['+639000000000'])]
    #[TestWith(['09000000000'])]
    public function test_pinas_sms_receives_its_expected_request_and_only_safe_audit_data_is_returned(string $recipient): void
    {
        config(['services.sms.url' => 'https://pinassms.com/api', 'services.sms.token' => 'synthetic-token']);
        Http::preventStrayRequests();
        Http::fake(['https://pinassms.com/api/sms/send' => Http::response(['id' => 'synthetic-message', 'debug' => 'private-provider-value'], 202)]);

        $result = app(SmsGateway::class)->send($recipient, 'Synthetic message', 'synthetic-id');

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'https://pinassms.com/api/sms/send'
            && $request->hasHeader('X-API-KEY', 'synthetic-token')
            && ! $request->hasHeader('Authorization')
            && $request->data() === ['recipient' => '639000000000', 'message' => 'Synthetic message']);
        $this->assertSame(['provider_message_id' => 'synthetic-message', 'response' => ['status' => 202]], $result);
    }

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
