<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\SmsGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use RuntimeException;
use Throwable;

class SmsProviderDriver implements SmsGateway
{
    public function __construct(private readonly HttpFactory $http) {}

    public function send(string $recipientNumber, string $message, string $idempotencyKey): array
    {
        try {
            $response = $this->http->baseUrl(config('services.sms.url'))
                ->withToken(config('services.sms.token'))
                ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                ->connectTimeout(3)
                ->timeout((int) config('services.sms.timeout', 10))
                ->retry([100, 500], 0, fn (Throwable $exception): bool => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && ($exception->response->serverError() || $exception->response->status() === 429)))
                ->post('/messages', ['to' => $recipientNumber, 'message' => $message]);

            $response->throw();
        } catch (ConnectionException|RequestException) {
            throw new RuntimeException('SMS provider delivery request failed.');
        }
        $payload = $response->json();

        return ['provider_message_id' => (string) ($payload['id'] ?? $idempotencyKey), 'response' => ['status' => $response->status()]];
    }
}
