<?php

namespace App\Jobs\Benefits;

use App\Services\Benefits\BenefitRecipientPreparation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PrepareBenefitRecipients implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [5, 30];

    public function __construct(public int $scheduleId, public string $token, public int $cursor, public int $requestedBy) {}

    public function handle(BenefitRecipientPreparation $preparation): void
    {
        $preparation->process($this->scheduleId, $this->token, $this->cursor, $this->requestedBy, true);
    }
}
