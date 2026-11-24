<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use UnexpectedValueException;

#[Fillable(['program_id', 'reference_number', 'period_key', 'eligibility_on', 'scheduled_at', 'ends_at', 'location', 'batch_name', 'barangay_ids', 'requirements', 'allow_representative', 'amount', 'available_funds', 'rule_snapshot', 'status', 'created_by', 'approved_by', 'approved_at', 'closed_at'])]
class BenefitSchedule extends Model
{
    protected function casts(): array
    {
        return ['eligibility_on' => 'date', 'scheduled_at' => 'datetime', 'ends_at' => 'datetime', 'approved_at' => 'datetime', 'closed_at' => 'datetime', 'barangay_ids' => 'array', 'rule_snapshot' => 'array', 'allow_representative' => 'boolean', 'amount' => 'decimal:2', 'available_funds' => 'decimal:2'];
    }

    protected function barangayIds(): Attribute
    {
        return Attribute::get(function (?string $value): ?array {
            $ids = $value === null ? null : Json::decode($value);
            if ($ids === null || is_array($ids)) {
                return $ids;
            }

            // Older imports stored the array inside a JSON string.
            $ids = is_string($ids) ? Json::decode($ids) : null;
            if (! is_array($ids) || ! array_is_list($ids)) {
                throw new UnexpectedValueException('Invalid barangay assignments on a benefit schedule.');
            }

            return $ids;
        });
    }

    public function payoutWindow(): string
    {
        return $this->scheduled_at->format('M j, Y g:i A')
            .($this->ends_at ? ' - '.$this->ends_at->format('g:i A') : '')
            .' (Philippine time)';
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
