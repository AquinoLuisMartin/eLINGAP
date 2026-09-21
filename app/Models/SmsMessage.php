<?php

namespace App\Models;

use App\Enums\SmsStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['senior_citizen_id', 'sms_template_id', 'created_by', 'recipient_number', 'message', 'status', 'provider_message_id', 'failure_reason', 'queued_at', 'sent_at'])]
class SmsMessage extends Model
{
    protected function casts(): array
    {
        return ['status' => SmsStatus::class, 'queued_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    public function seniorCitizen(): BelongsTo
    {
        return $this->belongsTo(SeniorCitizen::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class, 'sms_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(SmsDeliveryLog::class);
    }
}
