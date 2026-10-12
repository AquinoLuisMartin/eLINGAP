<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'agency', 'budget', 'starts_on', 'ends_on', 'status', 'description', 'created_by', 'benefit_code', 'government_level', 'benefit_rules', 'default_amount', 'provisional'])]
class Program extends Model
{
    public function benefitEntitlements(): HasMany
    {
        return $this->hasMany(BenefitEntitlement::class);
    }

    protected function casts(): array
    {
        return ['budget' => 'decimal:2', 'starts_on' => 'date', 'ends_on' => 'date', 'benefit_rules' => 'array', 'default_amount' => 'decimal:2', 'provisional' => 'boolean'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiary::class);
    }
}
