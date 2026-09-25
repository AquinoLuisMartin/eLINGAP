<?php

namespace Tests\Feature\Administration;

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_create_an_account_from_the_form(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->make();
        $password = Str::password();
        $payload = $user->only(['role_id', 'username', 'email', 'first_name', 'last_name']);

        $this->actingAs($admin)->get(route('administration.users.create'))->assertSee('Create user account');
        $this->post(route('administration.users.store'), [
            ...$payload,
            'username' => Str::upper($user->username),
            'email' => Str::upper($user->email),
            'password' => $password,
            'password_confirmation' => $password,
            'last_login_at' => now()->toDateTimeString(),
        ])->assertRedirect(route('administration.users.index'));

        $created = User::where('username', $user->username)->firstOrFail();
        $this->assertDatabaseHas('users', $payload);
        $this->assertTrue(Hash::check($password, $created->password_hash));
        $this->assertNull($created->last_login_at);
    }

    public function test_an_administrator_can_edit_another_account(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $newUsername = Str::lower(Str::random(20));

        $this->actingAs($admin)->get(route('administration.users.edit', $user))->assertSee('Edit user account');
        $this->put(route('administration.users.update', $user), [
            ...$user->only(['role_id', 'email', 'first_name', 'last_name']),
            'username' => $newUsername,
        ])->assertRedirect(route('administration.users.index'));

        $this->assertSame($newUsername, $user->fresh()->username);
    }

    public function test_account_creation_requires_valid_input(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('administration.users.store'), [])
            ->assertInvalid(['role_id', 'username', 'email', 'first_name', 'last_name', 'password']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_role_ids_must_be_scalar_integers(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->make();
        $password = Str::password();

        $this->actingAs($admin)->post(route('administration.users.store'), [
            ...$user->only(['username', 'email', 'first_name', 'last_name']),
            'role_id' => [$user->role_id],
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertInvalid(['role_id']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_staff_cannot_create_accounts(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->post(route('administration.users.store'), [])->assertForbidden();

        $this->assertDatabaseCount('users', 1);
    }

    #[TestWith(['POST', 'administration.users.store'])]
    #[TestWith(['PUT', 'administration.users.update'])]
    #[TestWith(['PATCH', 'administration.users.password.update'])]
    public function test_account_requests_authorize_staff_independently_of_route_roles(string $method, string $route): void
    {
        $staff = User::factory()->create();
        $target = User::factory()->create();
        $attributes = $target->fresh()->getAttributes();
        $this->withoutMiddleware(EnsureUserHasRole::class);

        $this->actingAs($staff)->call($method, route($route, ['user' => $target]))->assertForbidden();

        $this->assertDatabaseCount('users', 2);
        $this->assertSame($attributes, $target->fresh()->getAttributes());
    }

    public function test_inactive_administrators_are_rejected_by_account_requests(): void
    {
        $admin = User::factory()->admin()->inactive()->create();
        $this->withoutMiddleware(EnsureUserIsActive::class);

        $this->actingAs($admin)->post(route('administration.users.store'), [])->assertForbidden();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_an_administrator_cannot_demote_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();

        $this->actingAs($admin)->put(route('administration.users.update', $admin), [
            ...$admin->only(['username', 'email', 'first_name', 'last_name']),
            'role_id' => $staff->role_id,
        ])->assertForbidden();

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_password_reset_revokes_a_previously_authenticated_session(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create(['remember_token' => Str::random(60)]);
        $previousHash = $staff->password_hash;
        $password = Str::password();

        $this->actingAs($admin)->patch(route('administration.users.password.update', $staff), [
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertRedirect();

        $staff->refresh();
        $this->assertTrue(Hash::check($password, $staff->password_hash));
        $this->assertNull($staff->remember_token);
        $this->actingAs($staff)->withSession(['password_hash_web' => $previousHash])
            ->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
