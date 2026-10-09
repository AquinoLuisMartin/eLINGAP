<?php

namespace Tests\Feature\SeniorCitizens;

use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_photos_stay_private_when_the_default_disk_is_public(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        config(['filesystems.default' => 'public']);
        $staff = User::factory()->create();
        $barangay = Barangay::create(['name' => 'Test barangay', 'code' => 'TEST']);

        $response = $this->actingAs($staff)->post(route('senior-citizens.store'), [
            'barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => 'Applicant',
            'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Test address',
            'photo' => UploadedFile::fake()->image('photo.png', 100, 100),
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
