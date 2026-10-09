<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Seed the initial administrative accounts.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->call(RoleSeeder::class);

        $this->upsertUser(
            username: 'Marie Cruz',
            password: Str::password(),
            firstName: 'Marie',
            lastName: 'Cruz',
            role: UserRole::Admin,
        );

        $this->upsertUser(
            username: 'Pedro Silva',
            password: Str::password(),
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

        User::query()->firstOrCreate(
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
