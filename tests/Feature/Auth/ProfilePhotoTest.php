<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\ProfilePhotos;
use Aws\Command;
use Aws\MockHandler;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_s3_upload_reports_an_error_and_keeps_the_existing_photo(): void
    {
        Storage::fake('local');
        $this->actingAs($user = User::factory()->create());
        $previous = UploadedFile::fake()->image('previous.png')->store('profile-photos', 'local');
        $user->update(['profile_photo_path' => $previous]);
        config(['uploads.profile_photo_disk' => 's3']);
        $failure = new S3Exception('Synthetic private diagnostic detail', new Command('PutObject'), ['code' => 'AccessDenied']);
        $this->mock(ProfilePhotos::class)->shouldReceive('store')->once()->andThrow($failure);
        Exceptions::fake();
        Log::shouldReceive('error')->once()->with('Profile photo upload failed.', [
            'disk' => 's3',
            'failure_type' => S3Exception::class,
            'origin' => basename($failure->getFile()).':'.$failure->getLine(),
            'provider_status' => null,
            'provider_code' => 'AccessDenied',
        ]);

        $this->from(route('dashboard'))->post(route('profile-photo.update'), ['photo' => UploadedFile::fake()->image('synthetic.png')])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors(['photo' => 'The profile photo could not be saved. Please try again.']);

        $this->assertSame($previous, $user->fresh()->profile_photo_path);
        Storage::disk('local')->assertExists($previous);
    }

    public function test_replacement_cleans_up_the_current_photo_if_the_session_user_is_stale(): void
    {
        Storage::fake('local');
        $this->actingAs($user = User::factory()->create());
        $current = UploadedFile::fake()->image('current.png')->store('profile-photos', 'local');
        User::query()->whereKey($user->getKey())->update(['profile_photo_path' => $current]);

        $this->post(route('profile-photo.update'), ['photo' => UploadedFile::fake()->image('replacement.png')])
            ->assertSessionHasNoErrors();

        Storage::disk('local')->assertMissing($current);
        Storage::disk('local')->assertExists($user->fresh()->profile_photo_path);
    }

    public function test_s3_upload_replaces_a_local_photo_and_the_private_route_can_view_and_remove_it(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user);
        $file = UploadedFile::fake()->image('synthetic.png');
        $previous = $file->store('profile-photos', 'local');
        $user->update(['profile_photo_path' => $previous]);
        config([
            'uploads.profile_photo_disk' => 's3',
            'uploads.optimize_images' => false,
            'filesystems.disks.s3.bucket' => 'profile-photos',
            'filesystems.disks.s3.key' => 'synthetic',
            'filesystems.disks.s3.secret' => 'synthetic',
            'filesystems.disks.s3.region' => 'us-east-1',
            'filesystems.disks.s3.endpoint' => 'https://storage.example.test/storage/v1/s3',
            'filesystems.disks.s3.use_path_style_endpoint' => true,
        ]);
        $handler = new MockHandler([
            new Result,
            new Result(['ContentType' => 'image/png', 'Body' => $file->getContent()]),
            new Result,
        ]);
        $this->app->bind(S3Client::class, fn ($app, array $parameters) => new S3Client($parameters['args'] + ['handler' => $handler]));

        $this->get(route('profile-photo.show'))->assertOk();
        $this->post(route('profile-photo.update'), ['photo' => $file])->assertRedirect()->assertSessionHasNoErrors();
        $path = $user->fresh()->profile_photo_path;
        $this->assertStringStartsWith('profile-photos/s3/', $path);
        Storage::disk('local')->assertMissing($previous);
        $this->assertSame('PutObject', $handler->getLastCommand()->getName());
        $this->assertFalse($handler->getLastRequest()->hasHeader('x-amz-acl'));
        $this->assertSame('/storage/v1/s3/profile-photos/'.$path, $handler->getLastRequest()->getUri()->getPath());

        $this->get(route('profile-photo.show'))->assertOk()->assertHeader('Content-Type', 'image/png')->assertContent($file->getContent());
        $this->delete(route('profile-photo.destroy'))->assertRedirect();
        $this->assertSame('DeleteObject', $handler->getLastCommand()->getName());
        $this->assertSame($path, $handler->getLastCommand()['Key']);
        $this->assertNull($user->fresh()->profile_photo_path);
    }

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
            ->assertSee('Appearance')->assertSee('Dark mode')->assertSee('Log out')
            ->assertSeeHtml('role="switch"')->assertSeeHtml('aria-labelledby="account-dark-mode-label"')
            ->assertDontSeeHtml('class="workspace-theme-toggle"');
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
        $photo->assertOk()->assertHeader('Content-Type', 'image/webp');
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

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_staff_and_admin_uploads_use_lossless_compression(bool $admin): void
    {
        Storage::fake('local');
        $user = ($admin ? User::factory()->admin() : User::factory())->create();
        $file = UploadedFile::fake()->image('portrait.png', 400, 300);
        $original = file_get_contents($file->getPathname());
        $attributes = $user->getAttributes();

        $this->actingAs($user)->post(route('profile-photo.update'), ['photo' => $file])->assertRedirect()->assertSessionHasNoErrors();

        $path = $user->fresh()->profile_photo_path;
        $stored = Storage::disk('local')->get($path);
        $this->assertStringEndsWith('.webp', $path);
        $this->assertLessThan(strlen($original), strlen($stored));
        $this->assertSame([400, 300], array_slice(getimagesizefromstring($stored), 0, 2));
        foreach (['employee_id', 'role_id', 'password_hash', 'first_name', 'last_name'] as $attribute) {
            $this->assertSame($attributes[$attribute], $user->fresh()->getAttributes()[$attribute]);
        }
        $this->get(route('profile-photo.show'))->assertOk()->assertHeader('Content-Type', 'image/webp');
    }

    #[TestWith(['replace'])]
    #[TestWith(['remove'])]
    public function test_failed_account_update_keeps_the_previous_photo(string $action): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $path = UploadedFile::fake()->image('previous.png')->store('profile-photos', 'local');
        $user->update(['profile_photo_path' => $path]);
        $original = Storage::disk('local')->get($path);
        Exceptions::fake([RuntimeException::class]);
        Event::listen('eloquent.updating: '.User::class, fn () => throw new RuntimeException('Synthetic update failure'));
        $this->actingAs($user);

        ($action === 'replace'
            ? $this->post(route('profile-photo.update'), ['photo' => UploadedFile::fake()->image('replacement.png')])
            : $this->delete(route('profile-photo.destroy')))->assertServerError();

        $this->assertSame($path, $user->fresh()->profile_photo_path);
        $this->assertSame($original, Storage::disk('local')->get($path));
        $this->assertSame([$path], Storage::disk('local')->allFiles('profile-photos'));
        Exceptions::assertReported(RuntimeException::class);
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
