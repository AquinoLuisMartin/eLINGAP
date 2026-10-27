<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['senior_citizen_id', 'type', 'requested_by', 'requester_name', 'requester_relationship', 'previous_location', 'destination_municipality', 'destination_province', 'destination_barangay', 'destination_address', 'transferred_on', 'reason', 'senior_name', 'osca_id_number', 'recorded_by'])]
class SeniorCitizenTransfer extends Model
{
    protected function casts(): array
    {
        return ['transferred_on' => 'date'];
    }

    public function seniorCitizen(): BelongsTo
    {
        return $this->belongsTo(SeniorCitizen::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getDestinationAttribute(): string
    {
        return implode(', ', [$this->destination_address, $this->destination_barangay, $this->destination_municipality, $this->destination_province]);
    }
}
