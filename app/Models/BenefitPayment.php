<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['benefit_entitlement_id', 'benefit_schedule_id', 'reference_number', 'amount', 'claimant_type', 'external_reference', 'groceries_received', 'recorded_by', 'paid_at', 'reversed_by', 'reversed_at', 'reversal_reason'])]
class BenefitPayment extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime', 'reversed_at' => 'datetime', 'groceries_received' => 'boolean'];
    }

    public function entitlement(): BelongsTo
    {
        return $this->belongsTo(BenefitEntitlement::class, 'benefit_entitlement_id');
    }
}
