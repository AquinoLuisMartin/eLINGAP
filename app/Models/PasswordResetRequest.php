<?php

namespace App\Models;

use App\Enums\PasswordResetStatus;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Hidden(['recovery_code_hash'])]
class PasswordResetRequest extends Model
{
    protected function casts(): array
    {
        return ['status' => PasswordResetStatus::class, 'requested_at' => 'datetime', 'reviewed_at' => 'datetime',
            'identity_verified_at' => 'datetime', 'recovery_expires_at' => 'datetime', 'completed_at' => 'datetime', 'failed_attempts' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function expired(): bool
    {
        return $this->status === PasswordResetStatus::Approved && $this->recovery_expires_at->lte(now());
    }
}
