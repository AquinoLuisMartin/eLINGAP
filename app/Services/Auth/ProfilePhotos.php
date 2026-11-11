<?php

namespace App\Services\Auth;

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
            return $file->store('profile-photos', 'local');
        }

        $source = file_get_contents($file->getPathname());
        foreach (["Exif\0\0", 'ICC_PROFILE', 'eXIf', 'iCCP', 'sRGB', 'gAMA', 'cHRM', 'tEXt', 'iTXt', 'zTXt', 'acTL', 'EXIF', 'ICCP', 'ANIM', 'XMP'] as $metadata) {
            if (str_contains($source, $metadata)) {
                return $file->store('profile-photos', 'local');
            }
        }

        $image = @imagecreatefromstring($source);
        if ($image === false) {
            return $file->store('profile-photos', 'local');
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
            return $file->store('profile-photos', 'local');
        }

        $path = 'profile-photos/'.Str::uuid().'.webp';
        Storage::disk('local')->put($path, $compressed);

        return $path;
    }
}
