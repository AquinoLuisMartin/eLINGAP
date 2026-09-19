<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed ADMIN and OSCA_STAFF roles used by authentication routing.
     */
    public function run(): void
    {
        Role::query()->firstOrCreate(
            ['name' => UserRole::Admin->value],
            ['description' => 'System Administrator'],
        );

        Role::query()->firstOrCreate(
            ['name' => UserRole::OscaStaff->value],
            ['description' => 'OSCA Staff'],
        );
    }
}
