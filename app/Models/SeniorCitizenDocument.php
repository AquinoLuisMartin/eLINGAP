<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['senior_citizen_id', 'document_type', 'id_type_id', 'storage_disk', 'path', 'original_name', 'mime_type', 'size', 'stored_size', 'uploaded_by'])]
class SeniorCitizenDocument extends Model
{
    public function seniorCitizen(): BelongsTo
    {
        return $this->belongsTo(SeniorCitizen::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function idType(): BelongsTo
    {
        return $this->belongsTo(IdType::class);
    }
}
