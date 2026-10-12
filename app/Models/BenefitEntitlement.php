<?php

namespace App\Models;

use App\Enums\BenefitStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['program_id', 'senior_citizen_id', 'period_key', 'milestone_age', 'eligibility_on', 'amount', 'status', 'benefit_schedule_id', 'rule_snapshot', 'reviewed_by', 'reviewed_at', 'claimed_at', 'review_note'])]
class BenefitEntitlement extends Model
{
    protected function casts(): array
    {
        return ['status' => BenefitStatus::class, 'eligibility_on' => 'date', 'amount' => 'decimal:2', 'rule_snapshot' => 'array', 'reviewed_at' => 'datetime', 'claimed_at' => 'datetime'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function seniorCitizen(): BelongsTo
    {
        return $this->belongsTo(SeniorCitizen::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(BenefitSchedule::class, 'benefit_schedule_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BenefitPayment::class);
    }
}
