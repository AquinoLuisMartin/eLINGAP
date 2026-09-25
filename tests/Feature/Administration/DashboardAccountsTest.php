<?php

namespace Tests\Feature\Administration;

use App\Enums\LoginEvent;
use App\Livewire\Administration\Dashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DashboardAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_lists_database_accounts_with_pagination_and_no_hashes(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(16)->create();

        $component = Livewire::actingAs($admin)->test(Dashboard::class)
            ->call('navigate', 'users')
            ->assertSet('userCounts.total', 17)
            ->assertSet('userCounts.active', 17)
            ->assertDontSee($admin->password_hash);

        $this->assertCount(15, $component->get('filteredUsers')->items());
        $component->call('gotoPage', 2);
        $this->assertCount(2, $component->get('filteredUsers')->items());
    }

    public function test_directory_filters_by_email_and_status_and_resets_pagination(): void
    {
        $admin = User::factory()->admin()->create();
        $suspended = User::factory()->inactive()->create();

        $component = Livewire::actingAs($admin)->test(Dashboard::class)
            ->call('navigate', 'users')
            ->call('gotoPage', 2)
            ->set('userQuery', Str::upper($suspended->email))
            ->assertSee($suspended->username);

        $this->assertSame(1, $component->get('filteredUsers')->total());
        $this->assertSame(1, $component->get('filteredUsers')->currentPage());
        $component->set('userStatusFilter', 'Active');
        $this->assertSame(0, $component->get('filteredUsers')->total());
        $component->set('userStatusFilter', 'Suspended');
        $this->assertSame(1, $component->get('filteredUsers')->total());
    }

    public function test_profile_uses_the_authenticated_account_and_escapes_its_name(): void
    {
        $admin = User::factory()->admin()->create(['first_name' => '<script>alert(1)</script>']);

        Livewire::actingAs($admin)->test(Dashboard::class)
            ->set('profileModal', 'profile')
            ->assertSee($admin->email)
            ->assertSee($admin->full_name)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_an_administrator_cannot_suspend_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(Dashboard::class)
            ->call('toggleUserStatus', $admin->id)
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_staff_cannot_mount_the_administration_component(): void
    {
        Livewire::actingAs(User::factory()->create())->test(Dashboard::class)->assertForbidden();
    }

    public function test_a_deactivated_administrator_cannot_mount_the_component(): void
    {
        Livewire::actingAs(User::factory()->admin()->inactive()->create())
            ->test(Dashboard::class)->assertForbidden();
    }

    public function test_password_changes_are_persisted_and_logged(): void
    {
        $current = Str::password();
        $next = Str::password();
        $admin = User::factory()->admin()->create([
            'password_hash' => $current,
            'remember_token' => Str::random(60),
        ]);

        Livewire::actingAs($admin)->test(Dashboard::class)
            ->set('profileModal', 'password')
            ->set('passwordForm', ['current' => $current, 'next' => $next, 'confirm' => $next])
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('passwordForm', ['current' => '', 'next' => '', 'confirm' => ''])
            ->assertSet('profileModal', null)
            ->assertSet('toast', 'Password updated successfully.');

        $this->assertTrue(Hash::check($next, $admin->fresh()->password_hash));
        $this->assertNull($admin->fresh()->remember_token);
        $this->assertDatabaseHas('login_logs', ['user_id' => $admin->id, 'event' => LoginEvent::PasswordChanged->value, 'success' => true]);
    }

    #[TestWith(['current', 'current_password'])]
    #[TestWith(['next', Password::class])]
    #[TestWith(['confirm', 'same'])]
    public function test_invalid_password_changes_are_rejected_and_sensitive_fields_cleared(string $field, string $rule): void
    {
        $current = Str::password();
        $next = Str::password();
        $admin = User::factory()->admin()->create(['password_hash' => $current]);
        $hash = $admin->password_hash;
        $form = ['current' => $current, 'next' => $next, 'confirm' => $next];
        $form[$field] = $field === 'next' ? Str::password(3) : Str::password();

        Livewire::actingAs($admin)->test(Dashboard::class)
            ->set('profileModal', 'password')
            ->set('passwordForm', $form)
            ->call('updatePassword')
            ->assertHasErrors(['passwordForm.'.$field => $rule])
            ->assertSet('passwordForm', ['current' => '', 'next' => '', 'confirm' => ''])
            ->assertSet('profileModal', 'password');

        $this->assertSame($hash, $admin->fresh()->password_hash);
        $this->assertDatabaseEmpty('login_logs');
    }
}
