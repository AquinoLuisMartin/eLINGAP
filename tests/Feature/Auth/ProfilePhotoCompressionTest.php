<?php

namespace Tests\Feature\Auth;

use App\Services\Auth\ProfilePhotos;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProfilePhotoCompressionTest extends TestCase
{
    #[TestWith(['png'])]
    #[TestWith(['jpeg'])]
    #[TestWith(['webp'])]
    public function test_lossless_compression_preserves_dimensions_colours_and_transparency(string $format): void
    {
        Storage::fake('local');
        $image = imagecreatetruecolor(200, 150);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 10, 20, 30, 80));
        imagesetpixel($image, 100, 75, imagecolorallocatealpha($image, 200, 100, 50, 0));
        ob_start();
        match ($format) {
            'png' => imagepng($image, null, 0),
            'jpeg' => imagejpeg($image, null, 95),
            'webp' => imagewebp($image, null, 90),
        };
        $source = ob_get_clean();
        imagedestroy($image);
        $image = imagecreatefromstring($source);
        $file = UploadedFile::fake()->createWithContent('portrait.'.$format, $source);

        $path = app(ProfilePhotos::class)->store($file);

        $stored = Storage::disk('local')->get($path);
        $this->assertLessThanOrEqual(strlen($source), strlen($stored));
        if ($format === 'png') {
            $this->assertLessThan(strlen($source), strlen($stored));
        }
        $result = imagecreatefromstring($stored);
        $this->assertSame(200, imagesx($result));
        $this->assertSame(150, imagesy($result));
        for ($y = 0; $y < 150; $y++) {
            for ($x = 0; $x < 200; $x++) {
                $this->assertSame(imagecolorat($image, $x, $y), imagecolorat($result, $x, $y));
            }
        }
        imagedestroy($image);
        imagedestroy($result);
    }

    public function test_metadata_is_preserved_instead_of_discarded_by_reencoding(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->image('portrait.png');
        $png = file_get_contents($file->getPathname());
        $metadata = "tEXtDescription\0Synthetic metadata";
        $chunk = pack('N', strlen($metadata) - 4).$metadata.pack('N', crc32($metadata));
        $source = substr($png, 0, -12).$chunk.substr($png, -12);
        $file = UploadedFile::fake()->createWithContent('portrait.png', $source);

        $path = app(ProfilePhotos::class)->store($file);

        $this->assertSame($source, Storage::disk('local')->get($path));
    }

    public function test_already_optimized_images_are_never_made_larger(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->image('portrait.png');
        $first = app(ProfilePhotos::class)->store($file);
        $source = Storage::disk('local')->get($first);

        $path = app(ProfilePhotos::class)->store(UploadedFile::fake()->createWithContent('portrait.webp', $source));

        $this->assertSame($source, Storage::disk('local')->get($path));
    }

    public function test_disabled_optimization_preserves_original_bytes(): void
    {
        Storage::fake('local');
        config(['uploads.optimize_images' => false]);
        $file = UploadedFile::fake()->image('portrait.jpg');
        $source = file_get_contents($file->getPathname());

        $path = app(ProfilePhotos::class)->store($file);

        $this->assertSame($source, Storage::disk('local')->get($path));
    }
}
