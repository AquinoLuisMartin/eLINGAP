<?php

namespace App\Models;

use App\Enums\LoginEvent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'username_attempted', 'event', 'ip_address', 'user_agent', 'success', 'failure_reason'])]
class LoginLog extends Model
{
    // The table records when an event happened and is never updated afterwards.
    const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => LoginEvent::class,
            'success' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
