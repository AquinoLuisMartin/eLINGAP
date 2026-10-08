<?php

namespace App\Enums;

enum PayoutStatus: string
{
    case Pending = 'PENDING';
    case Released = 'RELEASED';
    case Failed = 'FAILED';
    case Voided = 'VOIDED';
}
