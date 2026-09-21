<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sms_message_id', 'status', 'provider_message_id', 'response_payload', 'recorded_at'])]
class SmsDeliveryLog extends Model
{
    protected function casts(): array
    {
        return ['response_payload' => 'array', 'recorded_at' => 'datetime'];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(SmsMessage::class, 'sms_message_id');
    }
}
