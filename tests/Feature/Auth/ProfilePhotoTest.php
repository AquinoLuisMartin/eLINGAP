<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_staff_and_admin_share_the_account_menu_layout(bool $admin): void
    {
        $user = ($admin ? User::factory()->admin() : User::factory())->create();
        $route = $admin ? 'administration.dashboard' : 'dashboard';

        $this->actingAs($user)->get(route($route))->assertOk()
            ->assertSee('account-menu-content', false)
            ->assertSee('Assigned office')->assertSee('Last sign-in')
            ->assertSee('Account status')->assertSee('Change password')
            ->assertSee('Dark mode')->assertSee('Log out');
    }

    public function test_account_menu_offers_a_profile_photo_upload(): void
    {
        $staff = User::factory()->create()->refresh();

        $this->actingAs($staff)->get(route('dashboard'))
            ->assertSee('action="'.route('profile-photo.update').'"', false)
            ->assertSee('name="photo"', false)
            ->assertSee('Upload profile photo', false)
            ->assertSee('Change photo', false)
            ->assertDontSee('Remove photo', false);
    }

    public function test_jpeg_upload_is_stored_privately_and_shown_on_the_account(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        config(['filesystems.default' => 'public']);
        $staff = User::factory()->create()->refresh();

        $this->actingAs($staff)->from(route('dashboard'))->post(route('profile-photo.update'), [
            'photo' => UploadedFile::fake()->image('portrait.jpg'),
            'profile_photo_path' => 'senior-photos/injected.jpg',
        ])->assertRedirect(route('dashboard'))->assertSessionHas('status', 'Profile photo updated.');

        $staff->refresh();
        $this->assertStringStartsWith('profile-photos/', $staff->profile_photo_path);
        $this->assertNotSame('senior-photos/injected.jpg', $staff->profile_photo_path);
        Storage::disk('local')->assertExists($staff->profile_photo_path);
        Storage::disk('public')->assertMissing($staff->profile_photo_path);

        $page = $this->get(route('dashboard'));
        $page->assertSee(route('profile-photo.show'), false)->assertSee('Remove photo', false);

        $photo = $this->get(route('profile-photo.show'));
        $photo->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertStringContainsString('no-store', (string) $photo->headers->get('Cache-Control'));
        $photo->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_replacing_a_photo_deletes_the_previous_file(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create()->refresh();

        $this->actingAs($staff)->post(route('profile-photo.update'), [
            'photo' => UploadedFile::fake()->image('first.jpg'),
        ])->assertRedirect();
        $previous = $staff->refresh()->profile_photo_path;

        $this->post(route('profile-photo.update'), [
            'photo' => UploadedFile::fake()->image('second.png'),
        ])->assertRedirect()->assertSessionHas('status', 'Profile photo updated.');

        $staff->refresh();
        Storage::disk('local')->assertMissing($previous);
        Storage::disk('local')->assertExists($staff->profile_photo_path);
        $this->assertNotSame($previous, $staff->profile_photo_path);
    }

    public function test_removing_a_photo_deletes_the_file_and_restores_initials(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create(['first_name' => 'Synthetic', 'last_name' => 'Operator'])->refresh();
        $this->actingAs($staff)->post(route('profile-photo.update'), [
            'photo' => UploadedFile::fake()->image('portrait.jpg'),
        ])->assertRedirect();
        $path = $staff->refresh()->profile_photo_path;

        $this->from(route('dashboard'))->delete(route('profile-photo.destroy'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', 'Profile photo removed.');

        $this->assertNull($staff->refresh()->profile_photo_path);
        Storage::disk('local')->assertMissing($path);
        $this->assertMatchesRegularExpression('/class="profile-avatar"[^>]*>\s*SO\s*</', $this->get(route('dashboard'))->getContent());
        $this->get(route('profile-photo.show'))->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('profile-photo.show'))->assertRedirect(route('login'));
        $this->post(route('profile-photo.update'))->assertRedirect(route('login'));
        $this->delete(route('profile-photo.destroy'))->assertRedirect(route('login'));
    }

    public function test_photo_route_does_not_serve_another_users_file(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create()->refresh();
        $other = User::factory()->create()->refresh();
        $path = UploadedFile::fake()->image('portrait.jpg')->store('profile-photos', 'local');
        $owner->update(['profile_photo_path' => $path]);

        $this->actingAs($other)->get(route('profile-photo.show'))->assertNotFound();
        Storage::disk('local')->assertExists($path);
        $this->assertSame($path, $owner->refresh()->profile_photo_path);
    }

    public function test_photo_route_refuses_a_path_outside_profile_storage(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create()->refresh();
        $path = UploadedFile::fake()->image('secret.jpg')->store('senior-photos', 'local');
        $staff->update(['profile_photo_path' => $path]);

        $this->actingAs($staff)->get(route('profile-photo.show'))->assertNotFound();
        Storage::disk('local')->assertExists($path);
    }

    public function test_missing_photo_is_rejected(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create()->refresh();

        $this->actingAs($staff)->from(route('dashboard'))->post(route('profile-photo.update'), [])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors(['photo' => 'Choose a profile photo.']);

        $this->assertNull($staff->refresh()->profile_photo_path);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create()->refresh();

        $this->actingAs($staff)->post(route('profile-photo.update'), [
            'photo' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
        ])->assertSessionHasErrors(['photo' => 'The profile photo must be a JPEG, PNG, or WebP image.']);

        $this->assertNull($staff->refresh()->profile_photo_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_gif_upload_is_rejected(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create()->refresh();

        $this->actingAs($staff)->post(route('profile-photo.update'), [
            'photo' => UploadedFile::fake()->image('portrait.gif'),
        ])->assertSessionHasErrors(['photo' => 'The profile photo must be a JPEG, PNG, or WebP image.']);

        $this->assertNull($staff->refresh()->profile_photo_path);
    }

    public function test_photo_over_two_megabytes_is_rejected(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create()->refresh();

        $this->actingAs($staff)->post(route('profile-photo.update'), [
            'photo' => UploadedFile::fake()->image('portrait.jpg')->size(2049),
        ])->assertSessionHasErrors(['photo' => 'The profile photo must be 2MB or smaller.']);

        $this->assertNull($staff->refresh()->profile_photo_path);
    }

    public function test_photo_over_4096_pixels_is_rejected(): void
    {
        Storage::fake('local');
        $staff = User::factory()->create()->refresh();

        $this->actingAs($staff)->post(route('profile-photo.update'), [
            'photo' => UploadedFile::fake()->image('portrait.jpg', 4097, 100),
        ])->assertSessionHasErrors(['photo' => 'The profile photo must be 4096 pixels or smaller on each side.']);

        $this->assertNull($staff->refresh()->profile_photo_path);
    }
}
