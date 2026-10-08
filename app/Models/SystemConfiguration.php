<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    public const DISPLAY_KEYS = ['organization_name', 'office_name', 'support_email', 'support_phone', 'sms_credits', 'backup_completed_at'];
}
