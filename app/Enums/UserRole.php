<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'ADMIN';
    case OscaStaff = 'OSCA_STAFF';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::OscaStaff => 'OSCA Staff',
        };
    }
}
