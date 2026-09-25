<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AccountProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seeding_creates_roles_without_accounts(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('roles', 2);
        $this->assertDatabaseEmpty('users');
    }

    public function test_seeding_does_not_change_existing_accounts(): void
    {
        $user = User::factory()->admin()->inactive()->create();
        $attributes = $user->fresh()->getAttributes();

        $this->seed();

        $this->assertSame($attributes, $user->fresh()->getAttributes());
        $this->assertDatabaseCount('users', 1);
    }

    public function test_an_administrator_can_be_created_interactively(): void
    {
        $user = User::factory()->make();
        $password = Str::password();

        $this->artisan('app:create-admin')
            ->expectsQuestion('Username', $user->username)
            ->expectsQuestion('Email address', $user->email)
            ->expectsQuestion('First name', $user->first_name)
            ->expectsQuestion('Last name', $user->last_name)
            ->expectsQuestion('Password', $password)
            ->expectsQuestion('Confirm password', $password)
            ->expectsOutput('Administrator account created.')
            ->assertSuccessful();

        $admin = User::sole();
        $this->assertSame($user->username, $admin->username);
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(Hash::check($password, $admin->password_hash));
    }

    #[TestWith(['duplicate'])]
    #[TestWith(['confirmation'])]
    public function test_invalid_account_details_do_not_create_or_overwrite_users(string $invalid): void
    {
        $existing = User::factory()->create();
        $attributes = $existing->fresh()->getAttributes();
        $candidate = User::factory()->make();
        $password = Str::password();

        $this->artisan('app:create-admin')
            ->expectsQuestion('Username', $invalid === 'duplicate' ? $existing->username : $candidate->username)
            ->expectsQuestion('Email address', $candidate->email)
            ->expectsQuestion('First name', $candidate->first_name)
            ->expectsQuestion('Last name', $candidate->last_name)
            ->expectsQuestion('Password', $password)
            ->expectsQuestion('Confirm password', $invalid === 'confirmation' ? Str::password() : $password)
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($attributes, $existing->fresh()->getAttributes());
    }

    public function test_non_interactive_provisioning_is_rejected(): void
    {
        $this->artisan('app:create-admin', ['--no-interaction' => true])
            ->expectsOutput('Run this command interactively to enter the account details securely.')
            ->assertFailed();

        $this->assertDatabaseEmpty('users');
    }
}
