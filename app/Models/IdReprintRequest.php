<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdReprintRequest extends Model
{
    public const REASONS = ['LOST' => 'Lost ID', 'DAMAGED' => 'Damaged ID', 'PRINTING_ERROR' => 'Printing Error', 'OTHER' => 'Other'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'consumed_at' => 'datetime'];
    }

    public function seniorCitizen(): BelongsTo
    {
        return $this->belongsTo(SeniorCitizen::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
