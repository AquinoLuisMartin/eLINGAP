<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'role_id' => fn () => Role::firstOrCreate(['name' => UserRole::OscaStaff->value])->id,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => Str::password(),
            'first_name' => fake()->firstName(),
            'middle_name' => null,
            'last_name' => fake()->lastName(),
            'name_suffix' => null,
            'is_active' => true,
            'last_login_at' => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role_id' => fn () => Role::firstOrCreate(['name' => UserRole::Admin->value])->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
