<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'body', 'is_active', 'created_by'])]
class SmsTemplate extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }
}
