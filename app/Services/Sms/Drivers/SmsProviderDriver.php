<?php

namespace App\Services\Sms\Drivers;

use App\Services\Sms\SmsDeliveryFailed;
use App\Services\Sms\SmsDeliveryRequiresReview;
use App\Services\Sms\SmsGateway;
use App\Services\Sms\SmsText;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Throwable;

class SmsProviderDriver implements SmsGateway
{
    public function __construct(private readonly HttpFactory $http) {}

    public function send(string $recipientNumber, string $message, string $idempotencyKey): array
    {
        $url = (string) config('services.sms.url');
        if (! parse_url($url, PHP_URL_HOST) || blank(config('services.sms.token'))) {
            throw new SmsDeliveryRequiresReview('SMS gateway URL or API key is missing or invalid.');
        }

        $response = strtolower((string) parse_url($url, PHP_URL_HOST)) === 'pinassms.com'
            ? $this->sendViaPinasSms($recipientNumber, $message)
            : $this->sendViaGenericGateway($url, $recipientNumber, $message, $idempotencyKey);

        return ['provider_message_id' => (string) ($response->json('id') ?? $idempotencyKey), 'response' => ['status' => $response->status()]];
    }

    private function sendViaPinasSms(string $recipientNumber, string $message): Response
    {
        $recipient = SmsText::mobile($recipientNumber);
        if ($recipient === null) {
            throw new SmsDeliveryRequiresReview('The recipient does not have a valid Philippine mobile number.');
        }

        try {
            $response = $this->http->acceptJson()
                ->withHeaders(['X-API-KEY' => config('services.sms.token')])
                ->withoutRedirecting()
                ->connectTimeout(3)
                ->timeout((int) config('services.sms.timeout', 10))
                ->post('https://pinassms.com/api/sms/send', ['recipient' => ltrim($recipient, '+'), 'message' => $message]);
        } catch (ConnectionException) {
            throw new SmsDeliveryRequiresReview('PinasSMS could not be reached. Check its outbox before retrying.');
        }

        if (! $response->successful() || $response->json('success') === false) {
            throw new SmsDeliveryRequiresReview('PinasSMS did not confirm the send (HTTP '.$response->status().'). Check its outbox before retrying.');
        }

        return $response;
    }

    private function sendViaGenericGateway(string $url, string $recipientNumber, string $message, string $idempotencyKey): Response
    {
        try {
            $response = $this->http->baseUrl($url)
                ->withToken(config('services.sms.token'))
                ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                ->connectTimeout(3)
                ->timeout((int) config('services.sms.timeout', 10))
                ->retry([100, 500], 0, fn (Throwable $exception): bool => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && ($exception->response->serverError() || $exception->response->status() === 429)))
                ->post('/messages', ['to' => $recipientNumber, 'message' => $message]);

            $response->throw();
        } catch (ConnectionException|RequestException) {
            throw new SmsDeliveryFailed('SMS provider delivery request failed.');
        }

        return $response;
    }
}
