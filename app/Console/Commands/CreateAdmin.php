<?php

namespace App\Console\Commands;

use App\Enums\LoginEvent;
use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use App\Rules\EmployeeIdInput;
use App\Rules\NewPassword;
use App\Services\Auth\AuthAudit;
use App\Services\Auth\Credentials;
use App\Services\Auth\EmployeeId;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {--recover : Recover an administrator after approved identity verification}';

    protected $description = 'Create an administrator using interactive prompts';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Run this command interactively to enter the account details securely.');

            return self::FAILURE;
        }

        if ($this->option('recover')) {
            return $this->recover();
        }

        $data = [
            'employee_id' => EmployeeId::normalize($this->ask('Employee ID') ?? ''),
            'first_name' => trim($this->ask('First name') ?? ''),
            'last_name' => trim($this->ask('Last name') ?? ''),
            'password' => $this->secret('Password', false),
            'password_confirmation' => $this->secret('Confirm password', false),
        ];

        $validator = Validator::make($data, [
            'employee_id' => ['required', 'string', new EmployeeIdInput],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'confirmed', new NewPassword(new User(collect($data)->only(['employee_id', 'first_name', 'last_name'])->all()))],
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

            $user = User::create([
                ...$data,
                'role_id' => $role->id,
                'password_hash' => $validator->validated()['password'],
                'is_active' => true,
            ]);
            AuthAudit::record('user.created', $user, null, ['source' => 'console']);
        });

        $this->info('Administrator account created.');

        return self::SUCCESS;
    }

    private function recover(): int
    {
        if (! $this->confirm('Has the administrator identity been verified under the approved office procedure?')) {
            return self::FAILURE;
        }
        $employeeId = EmployeeId::normalize($this->ask('Employee ID') ?? '');
        $password = $this->secret('New temporary password', false);
        $confirmation = $this->secret('Confirm password', false);
        try {
            DB::transaction(function () use ($employeeId, $password, $confirmation): void {
                $user = User::query()->where('employee_id', $employeeId)->lockForUpdate()->first();
                if (! $user || ! $user->isAdmin() || ! $user->is_active) {
                    throw ValidationException::withMessages(['employee_id' => 'An eligible administrator account was not found.']);
                }
                validator(['password' => $password, 'password_confirmation' => $confirmation], ['password' => ['required', 'string', 'confirmed', new NewPassword($user)]])->validate();
                app(Credentials::class)->replaceLocked($user, $password, LoginEvent::PasswordReset, mustChange: true);
                AuthAudit::record('user.console_recovery', $user, null, ['source' => 'console', 'identity_verified' => true]);
            });
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }
        $this->info('Administrator recovery completed. A password change is required at the next sign-in.');

        return self::SUCCESS;
    }
}
