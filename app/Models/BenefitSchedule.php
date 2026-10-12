<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['program_id', 'reference_number', 'period_key', 'eligibility_on', 'scheduled_at', 'location', 'batch_name', 'barangay_ids', 'requirements', 'allow_representative', 'amount', 'available_funds', 'rule_snapshot', 'status', 'created_by', 'approved_by', 'approved_at', 'closed_at'])]
class BenefitSchedule extends Model
{
    protected function casts(): array
    {
        return ['eligibility_on' => 'date', 'scheduled_at' => 'datetime', 'approved_at' => 'datetime', 'closed_at' => 'datetime', 'barangay_ids' => 'array', 'rule_snapshot' => 'array', 'allow_representative' => 'boolean', 'amount' => 'decimal:2', 'available_funds' => 'decimal:2'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(BenefitEntitlement::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(BenefitScheduleRecipient::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BenefitPayment::class);
    }
}
