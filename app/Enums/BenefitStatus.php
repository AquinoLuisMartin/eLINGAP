<?php

namespace App\Enums;

enum BenefitStatus: string
{
    case Pending = 'PENDING';
    case Unpaid = 'UNPAID';
    case Unclaimed = 'UNCLAIMED';
    case Claimed = 'CLAIMED';

    public function label(): string
    {
        return ucfirst(strtolower($this->value));
    }
}
