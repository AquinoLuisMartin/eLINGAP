<?php

namespace App\Models;

use App\Enums\SeniorCitizenStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['barangay_id', 'registration_number', 'first_name', 'middle_name', 'last_name', 'name_suffix', 'birth_date', 'sex', 'civil_status', 'address', 'contact_number', 'osca_id_number', 'status', 'verified_at', 'verified_by'])]
class SeniorCitizen extends Model
{
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'verified_at' => 'datetime',
            'status' => SeniorCitizenStatus::class,
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

    #[Scope]
    protected function searchable(Builder $query, string $term): Builder
    {
        $term = '%'.mb_strtolower($term).'%';

        return $query->where(function (Builder $query) use ($term) {
            $query->whereRaw('lower(registration_number) like ?', [$term])
                ->orWhereRaw('lower(first_name) like ?', [$term])
                ->orWhereRaw('lower(last_name) like ?', [$term])
                ->orWhereRaw('lower(osca_id_number) like ?', [$term]);
        });
    }

    public function getFullNameAttribute(): string
    {
        return implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name, $this->name_suffix]));
    }
}
