<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'issuing_agency', 'category', 'is_active', 'sort_order'])]
class IdType extends Model
{
    public const NO_VALID_ID = 'no_valid_id';

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
