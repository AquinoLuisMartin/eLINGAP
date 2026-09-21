<?php

namespace App\Services\Sms;

interface SmsGateway
{
    /** @return array{provider_message_id: string, response: array<string, mixed>} */
    public function send(string $recipientNumber, string $message, string $idempotencyKey): array;
}
