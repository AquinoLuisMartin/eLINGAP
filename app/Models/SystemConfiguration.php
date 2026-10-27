<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key'])]
class SystemConfiguration extends Model
{
    public const OFFICE_KEYS = ['organization_name', 'office_name', 'municipality', 'province', 'support_email', 'support_phone', 'office_address', 'office_hours'];

    public const DISPLAY_KEYS = [...self::OFFICE_KEYS, 'sms_credits', 'backup_completed_at'];
}
