<?php

namespace App\Services\Benefits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class BenefitAudit
{
    public static function record(string $action, Model $record, array $changes = []): void
    {
        AuditLog::create(['user_id' => auth()->id(), 'action' => $action, 'auditable_type' => $record::class, 'auditable_id' => $record->getKey(), 'new_values' => $changes, 'ip_address' => request()->ip()]);
    }
}
