<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['program_id', 'reference_number', 'scheduled_on', 'amount', 'status', 'created_by'])]
class PayoutSchedule extends Model
{
    protected function casts(): array
    {
        return ['scheduled_on' => 'date', 'amount' => 'decimal:2'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }
}
