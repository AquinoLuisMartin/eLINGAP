<?php

namespace Tests\Feature\SeniorCitizens;

use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToWriteFile;
use Mockery;
use Tests\TestCase;

class PrivateUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_private_uploads_roll_back_registration_and_document_metadata(): void
    {
        $staff = User::factory()->create();
        $barangay = Barangay::create(['name' => 'Synthetic barangay', 'code' => 'SYN']);
        $driver = Mockery::mock(FilesystemOperator::class);
        $driver->shouldReceive('writeStream')->once()->andThrow(UnableToWriteFile::atLocation('synthetic', 'Synthetic write failure'));
        Storage::set('local', new FilesystemAdapter($driver, Mockery::mock(FlysystemAdapter::class), config('filesystems.disks.local')));
        Exceptions::fake([UnableToWriteFile::class]);

        $this->actingAs($staff)->post(route('senior-citizens.store'), [
            'requirements_status' => 'REVIEW', 'requirements_checks' => ['identity' => false, 'residency' => false, 'authorization' => false], 'review_reason' => 'Synthetic document needs checking',
            'barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => 'Applicant',
            'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Synthetic address', 'contact_number' => '09171234567',
            'photo' => UploadedFile::fake()->createWithContent('synthetic.jpg', file_get_contents(base_path('tests/Fixtures/id-photo.jpg'))),
        ])->assertServerError();

        $this->assertDatabaseCount('senior_citizens', 0);
        $this->assertDatabaseCount('senior_citizen_documents', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        Exceptions::assertReported(UnableToWriteFile::class);
    }

    public function test_photos_stay_private_when_the_default_disk_is_public(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        config(['filesystems.default' => 'public']);
        $staff = User::factory()->create();
        $barangay = Barangay::create(['name' => 'Test barangay', 'code' => 'TEST']);

        $response = $this->actingAs($staff)->post(route('senior-citizens.store'), [
            'requirements_status' => 'REVIEW', 'requirements_checks' => ['identity' => false, 'residency' => false, 'authorization' => false], 'review_reason' => 'Synthetic document needs checking',
            'barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => 'Applicant',
            'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Test address', 'contact_number' => '09171234567',
            'photo' => UploadedFile::fake()->createWithContent('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+nmfwAAAAASUVORK5CYII=')),
        ]);

        $senior = SeniorCitizen::sole();
        $response->assertRedirect(route('senior-citizens.show', $senior));
        $photo = $senior->documents()->sole();
        Storage::disk('local')->assertExists($photo->path);
        Storage::disk('public')->assertMissing($photo->path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'senior_citizen.created', 'user_id' => $staff->id]);

        $photoResponse = $this->get(route('senior-citizens.photo', $senior))->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $photoResponse->headers->get('Cache-Control'));

        auth()->logout();
        $this->get(route('senior-citizens.photo', $senior))->assertRedirect(route('login'));
    }
}
