<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'is_active'])]
class Barangay extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function seniorCitizens(): HasMany
    {
        return $this->hasMany(SeniorCitizen::class);
    }
}
