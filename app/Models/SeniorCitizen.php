<?php

namespace App\Models;

use App\Enums\SeniorCitizenStatus;
use App\Services\Sms\SmsText;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['barangay_id', 'registration_number', 'first_name', 'middle_name', 'last_name', 'name_suffix', 'birth_date', 'sex', 'civil_status', 'address', 'contact_number', 'osca_id_number', 'status', 'verified_at', 'verified_by', 'died_on', 'death_declared_at', 'death_declared_by', 'applicant_type', 'representative_name', 'representative_relationship', 'representative_contact', 'requirements_checks', 'review_reason'])]
class SeniorCitizen extends Model
{
    public function benefitEntitlements(): HasMany
    {
        return $this->hasMany(BenefitEntitlement::class);
    }

    public function smsContact(): ?string
    {
        return SmsText::mobile($this->contact_number)
            ?? SmsText::mobile($this->representative_contact);
    }

    protected function casts(): array
    {
        return [
            'requirements_checks' => 'array',
            'birth_date' => 'date',
            'verified_at' => 'datetime',
            'status' => SeniorCitizenStatus::class,
            'died_on' => 'date',
            'death_declared_at' => 'datetime',
            'id_generated_at' => 'datetime',
            'is_bedridden' => 'boolean',
            'is_indigent' => 'boolean',
            'has_national_pension' => 'boolean',
            'has_local_pension' => 'boolean',
            'classification_verified_at' => 'datetime',
            'tss_verified_at' => 'datetime',
        ];
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(SeniorCitizenHistory::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SeniorCitizenDocument::class);
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiary::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(SeniorCitizenTransfer::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->beneficiaries()->where('status', 'ACTIVE');
    }

    #[Scope]
    protected function searchable(Builder $query, string $term, string $field = 'all'): Builder
    {
        $term = trim($term);
        if ($field === 'osca_id' || $field === 'registration') {
            return $query->where($field === 'osca_id' ? 'osca_id_number' : 'registration_number', $term);
        }
        $literal = fn (string $value) => '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower($value)).'%';

        return $query->where(function (Builder $query) use ($term, $field, $literal) {
            if ($field !== 'name') {
                $query->whereRaw('lower(registration_number) like ?', [$literal($term)])
                    ->orWhereRaw('lower(osca_id_number) like ?', [$literal($term)]);
            }
            $query->orWhere(function (Builder $names) use ($term, $literal) {
                foreach (preg_split('/[\s,]+/', $term, -1, PREG_SPLIT_NO_EMPTY) as $part) {
                    $names->where(function (Builder $columns) use ($part, $literal) {
                        foreach (['first_name', 'middle_name', 'last_name'] as $column) {
                            $columns->orWhereRaw('lower('.$column.') like ?', [$literal($part)]);
                        }
                    });
                }
            });
        });
    }

    public function getFullNameAttribute(): string
    {
        return implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name, $this->name_suffix]));
    }
}
