<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\SmsGateway;
use Illuminate\Http\Client\Factory as HttpFactory;

class SmsProviderDriver implements SmsGateway
{
    public function __construct(private readonly HttpFactory $http) {}

    public function send(string $recipientNumber, string $message, string $idempotencyKey): array
    {
        $response = $this->http->baseUrl(config('services.sms.url'))
            ->withToken(config('services.sms.token'))
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->connectTimeout(3)
            ->timeout((int) config('services.sms.timeout', 10))
            ->retry([100, 500], 0)
            ->post('/messages', ['to' => $recipientNumber, 'message' => $message]);

        $response->throw();
        $payload = $response->json();

        return ['provider_message_id' => (string) ($payload['id'] ?? $idempotencyKey), 'response' => $payload];
    }
}
