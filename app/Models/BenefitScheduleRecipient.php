<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['benefit_schedule_id', 'benefit_entitlement_id', 'outcome'])]
class BenefitScheduleRecipient extends Model
{
    public function entitlement(): BelongsTo
    {
        return $this->belongsTo(BenefitEntitlement::class, 'benefit_entitlement_id');
    }
}
