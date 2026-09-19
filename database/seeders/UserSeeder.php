<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed local development accounts for Admin and OSCA Staff testing.
     */
    public function run(): void
    {
        $adminRoleId = Role::query()->where('name', UserRole::Admin->value)->value('id');
        $staffRoleId = Role::query()->where('name', UserRole::OscaStaff->value)->value('id');

        User::query()->updateOrCreate(
            ['email' => 'adminstamaria@bulacan.gov.ph'],
            [
                'role_id' => $adminRoleId,
                'username' => 'adminstamaria',
                'password_hash' => 'password123',
                'first_name' => 'Admin',
                'last_name' => 'StaMaria',
                'is_active' => true,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'stamariastaff@bulacan.gov.ph'],
            [
                'role_id' => $staffRoleId,
                'username' => 'stamariastaff',
                'password_hash' => 'password123',
                'first_name' => 'OSCA',
                'last_name' => 'Staff',
                'is_active' => true,
            ],
        );
    }
}
