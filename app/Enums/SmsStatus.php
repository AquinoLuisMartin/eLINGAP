<?php

namespace App\Enums;

enum SmsStatus: string
{
    case Queued = 'QUEUED';
    case Sent = 'SENT';
    case Failed = 'FAILED';
}
