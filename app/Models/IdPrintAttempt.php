<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdPrintAttempt extends Model
{
    protected function casts(): array
    {
        return ['generated_at' => 'datetime', 'confirmed_at' => 'datetime', 'failed_at' => 'datetime'];
    }

    public function seniorCitizen(): BelongsTo
    {
        return $this->belongsTo(SeniorCitizen::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function reprintRequest(): BelongsTo
    {
        return $this->belongsTo(IdReprintRequest::class, 'reprint_request_id');
    }
}
