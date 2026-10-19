<?php

namespace Tests\Feature\SeniorCitizens;

use App\Services\SeniorCitizens\PrivateUploads;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateUploadsTest extends TestCase
{
    public function test_official_png_compression_reduces_bytes_without_changing_pixels_or_transparency(): void
    {
        Storage::fake('local');
        $image = imagecreatetruecolor(400, 400);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 10, 20, 30, 80));
        ob_start();
        imagepng($image, null, 0);
        $original = ob_get_clean();
        imagedestroy($image);
        $file = UploadedFile::fake()->createWithContent('synthetic-seal.png', $original);

        $path = app(PrivateUploads::class)->storeOfficialImage($file);

        $stored = Storage::disk('local')->get($path);
        $this->assertLessThan(strlen($original), strlen($stored));
        $result = imagecreatefromstring($stored);
        $this->assertSame(400, imagesx($result));
        $this->assertSame(400, imagesy($result));
        $this->assertSame(['red' => 10, 'green' => 20, 'blue' => 30, 'alpha' => 80], imagecolorsforindex($result, imagecolorat($result, 200, 200)));
        imagedestroy($result);
    }

    public function test_disabled_compression_preserves_original_bytes(): void
    {
        Storage::fake('local');
        config(['uploads.optimize_images' => false]);
        $file = UploadedFile::fake()->image('synthetic-seal.png', 20, 20);
        $original = file_get_contents($file->getPathname());

        $path = app(PrivateUploads::class)->storeOfficialImage($file);

        $this->assertSame($original, Storage::disk('local')->get($path));
    }

    public function test_jpeg_official_signatures_are_preserved_without_lossy_reencoding(): void
    {
        Storage::fake('local');
        $original = file_get_contents(base_path('tests/Fixtures/id-photo.jpg'));
        $file = UploadedFile::fake()->createWithContent('synthetic-signature.jpg', $original);

        $path = app(PrivateUploads::class)->storeOfficialImage($file);

        $this->assertSame($original, Storage::disk('local')->get($path));
    }
}
