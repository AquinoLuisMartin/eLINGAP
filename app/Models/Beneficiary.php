<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['program_id', 'senior_citizen_id', 'application_id', 'enrolled_on', 'status', 'assigned_by', 'assigned_at'])]
class Beneficiary extends Model
{
    protected function casts(): array
    {
        return ['enrolled_on' => 'date', 'assigned_at' => 'datetime'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function seniorCitizen(): BelongsTo
    {
        return $this->belongsTo(SeniorCitizen::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
