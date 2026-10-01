<?php

namespace App\Models;

use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payout_schedule_id', 'senior_citizen_id', 'amount', 'status', 'released_at', 'released_by'])]
class Payout extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'released_at' => 'datetime', 'status' => PayoutStatus::class];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(PayoutSchedule::class, 'payout_schedule_id');
    }

    public function seniorCitizen(): BelongsTo
    {
        return $this->belongsTo(SeniorCitizen::class);
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }
}
