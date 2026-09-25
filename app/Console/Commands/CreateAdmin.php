<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Create an administrator using interactive prompts';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Run this command interactively to enter the account details securely.');

            return self::FAILURE;
        }

        $data = [
            'username' => Str::lower(trim($this->ask('Username') ?? '')),
            'email' => Str::lower(trim($this->ask('Email address') ?? '')),
            'first_name' => trim($this->ask('First name') ?? ''),
            'last_name' => trim($this->ask('Last name') ?? ''),
            'password' => $this->secret('Password', false),
            'password_confirmation' => $this->secret('Confirm password', false),
        ];

        $validator = Validator::make($data, [
            'username' => ['required', 'string', 'min:3', 'max:100', 'regex:/^[a-z0-9._-]+$/', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($validator): void {
            $data = $validator->safe()->except('password');
            $role = Role::firstOrCreate(['name' => UserRole::Admin->value], ['description' => 'System Administrator']);

            User::create([
                ...$data,
                'role_id' => $role->id,
                'password_hash' => $validator->validated()['password'],
                'is_active' => true,
            ]);
        });

        $this->info('Administrator account created.');

        return self::SUCCESS;
    }
}
