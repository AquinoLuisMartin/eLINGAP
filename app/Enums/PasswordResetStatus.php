<?php

namespace App\Enums;

enum PasswordResetStatus: string
{
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case Completed = 'COMPLETED';
    case Expired = 'EXPIRED';
}
