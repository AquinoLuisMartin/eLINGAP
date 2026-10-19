<?php

namespace App\Services\SeniorCitizens;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PrivateUploads
{
    public function storeOfficialImage(UploadedFile $file): string
    {
        $size = @getimagesize($file->getPathname());
        $limit = ini_parse_quantity(ini_get('memory_limit'));
        if (! config('uploads.optimize_images') || ! extension_loaded('gd') || $file->getMimeType() !== 'image/png'
            || ! $size || $size[0] * $size[1] > 12000000
            || ($limit > 0 && memory_get_usage(true) + $size[0] * $size[1] * 16 + 16777216 > $limit)) {
            return $file->store('id-official-assets', 'local');
        }
        $source = file_get_contents($file->getPathname());
        if (str_contains($source, 'acTL') || str_contains($source, 'eXIf') || str_contains($source, "Exif\0\0")) {
            return $file->store('id-official-assets', 'local');
        }
        $image = @imagecreatefromstring($source);
        if ($image === false) {
            return $file->store('id-official-assets', 'local');
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);
        ob_start();
        try {
            $saved = imagepng($image, null, 9);
            $compressed = ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($image);
        }
        if (! $saved || strlen($compressed) >= strlen($source)) {
            return $file->store('id-official-assets', 'local');
        }
        $path = 'id-official-assets/'.Str::uuid().'.png';
        Storage::disk('local')->put($path, $compressed);

        return $path;
    }
}
