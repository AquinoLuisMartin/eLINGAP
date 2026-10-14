<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['senior_citizen_id', 'verification_token_hash', 'verification_token', 'created_by'])]
class SeniorCitizenIdCredential extends Model
{
    protected $hidden = ['verification_token', 'verification_token_hash'];

    protected function casts(): array
    {
        return ['verification_token' => 'encrypted'];
    }

    public function seniorCitizen(): BelongsTo
    {
        return $this->belongsTo(SeniorCitizen::class);
    }
}
