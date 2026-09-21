<?php

namespace App\Enums;

enum SeniorCitizenStatus: string
{
    case Pending = 'PENDING';
    case Verified = 'VERIFIED';
    case Archived = 'ARCHIVED';
}
