<?php

namespace App\Services\Auth;

use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfilePhotos
{
    public function store(UploadedFile $file): string
    {
        $size = @getimagesize($file->getPathname());
        $limit = ini_parse_quantity(ini_get('memory_limit'));
        if (! config('uploads.optimize_images') || ! function_exists('imagewebp') || ! defined('IMG_WEBP_LOSSLESS')
            || ! $size || $size[0] * $size[1] > 12000000
            || ($limit > 0 && memory_get_usage(true) + $size[0] * $size[1] * 16 + 16777216 > $limit)) {
            return $this->storeOriginal($file);
        }

        $source = file_get_contents($file->getPathname());
        foreach (["Exif\0\0", 'ICC_PROFILE', 'eXIf', 'iCCP', 'sRGB', 'gAMA', 'cHRM', 'tEXt', 'iTXt', 'zTXt', 'acTL', 'EXIF', 'ICCP', 'ANIM', 'XMP'] as $metadata) {
            if (str_contains($source, $metadata)) {
                return $this->storeOriginal($file);
            }
        }

        $image = @imagecreatefromstring($source);
        if ($image === false) {
            return $this->storeOriginal($file);
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        ob_start();
        try {
            $saved = imagewebp($image, null, IMG_WEBP_LOSSLESS);
            $compressed = ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($image);
        }

        if (! $saved || $compressed === '' || strlen($compressed) >= strlen($source)) {
            return $this->storeOriginal($file);
        }

        return $this->write(Str::uuid().'.webp', $compressed, 'image/webp');
    }

    public function usesS3(string $path): bool
    {
        return str_starts_with($path, 'profile-photos/s3/');
    }

    public function readFromS3(string $path): ?Result
    {
        try {
            return $this->s3()->getObject($this->object($path));
        } catch (S3Exception $exception) {
            if ($exception->getAwsErrorCode() === 'NoSuchKey') {
                return null;
            }

            throw $exception;
        }
    }

    public function delete(string $path): void
    {
        if ($this->usesS3($path)) {
            $this->s3()->deleteObject($this->object($path));

            return;
        }

        Storage::disk('local')->delete($path);
    }

    private function storeOriginal(UploadedFile $file): string
    {
        if (config('uploads.profile_photo_disk') === 'local') {
            return $file->store('profile-photos', 'local');
        }

        return $this->write($file->hashName(), $file->getContent(), $file->getMimeType());
    }

    private function write(string $name, string $contents, string $mime): string
    {
        $disk = config('uploads.profile_photo_disk');
        if ($disk === 's3') {
            $path = 'profile-photos/s3/'.$name;
            $this->s3()->putObject($this->object($path) + ['Body' => $contents, 'ContentType' => $mime]);

            return $path;
        }

        if ($disk !== 'local') {
            throw new \InvalidArgumentException('Invalid profile photo disk.');
        }

        $path = 'profile-photos/'.$name;
        Storage::disk('local')->put($path, $contents);

        return $path;
    }

    private function object(string $path): array
    {
        return ['Bucket' => config('filesystems.disks.s3.bucket'), 'Key' => $path];
    }

    private function s3(): S3Client
    {
        $disk = config('filesystems.disks.s3');

        return app(S3Client::class, ['args' => [
            'region' => $disk['region'],
            'endpoint' => $disk['endpoint'],
            'credentials' => ['key' => $disk['key'], 'secret' => $disk['secret']],
            'use_path_style_endpoint' => $disk['use_path_style_endpoint'],
            'request_checksum_calculation' => 'when_required',
            'response_checksum_validation' => 'when_required',
            'http' => ['connect_timeout' => 3, 'timeout' => 10],
        ]]);
    }
}
