<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the initial administrative accounts.
     */
    public function run(): void
    {
        $this->upsertUser(
            username: 'Marie Cruz',
            password: 'admin123',
            firstName: 'Marie',
            lastName: 'Cruz',
            role: UserRole::Admin,
        );

        $this->upsertUser(
            username: 'Pedro Silva',
            password: 'osca123',
            firstName: 'Pedro',
            lastName: 'Silva',
            role: UserRole::OscaStaff,
        );
    }

    private function upsertUser(
        string $username,
        string $password,
        string $firstName,
        string $lastName,
        UserRole $role,
    ): void {
        $roleRecord = Role::query()->where('name', $role->value)->firstOrFail();

        User::query()->updateOrCreate(
            ['username' => $username],
            [
                'role_id' => $roleRecord->id,
                'password_hash' => $password,
                'email' => null,
                'first_name' => $firstName,
                'middle_name' => null,
                'last_name' => $lastName,
                'name_suffix' => null,
                'is_active' => true,
            ],
        );
    }
}
