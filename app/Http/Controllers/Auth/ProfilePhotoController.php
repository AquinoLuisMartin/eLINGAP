<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateAccountRequest;
use App\Services\Auth\ProfilePhotos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ProfilePhotoController extends Controller
{
    public function show(Request $request, ProfilePhotos $photos): Response
    {
        $path = $request->user()->profile_photo_path;
        abort_unless($this->validPhotoPath($path), 404);

        $remote = $photos->usesS3($path);
        if ($remote) {
            $object = $photos->readFromS3($path);
            abort_if($object === null, 404);
            $mime = $object['ContentType'];
        } else {
            abort_unless(Storage::disk('local')->exists($path), 404);
            $mime = Storage::disk('local')->mimeType($path);
        }
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

        $headers = [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="profile-photo"',
        ];
        $response = $remote
            ? response((string) $object['Body'], 200, $headers)
            : response()->file(Storage::disk('local')->path($path), $headers);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    public function update(UpdateAccountRequest $request, ProfilePhotos $photos): RedirectResponse
    {
        $user = $request->user();
        $path = $photos->store($request->file('photo'));

        try {
            $previous = $user->profile_photo_path;
            $user->update(['profile_photo_path' => $path]);
        } catch (Throwable $exception) {
            rescue(fn () => $photos->delete($path));

            throw $exception;
        }

        if ($previous !== $path && $this->validPhotoPath($previous)) {
            rescue(fn () => $photos->delete($previous));
        }

        return back()->with('status', 'Profile photo updated.');
    }

    public function destroy(Request $request, ProfilePhotos $photos): RedirectResponse
    {
        $user = $request->user();
        $previous = $user->profile_photo_path;
        $user->update(['profile_photo_path' => null]);
        if ($this->validPhotoPath($previous)) {
            rescue(fn () => $photos->delete($previous));
        }

        return back()->with('status', 'Profile photo removed.');
    }

    private function validPhotoPath(mixed $path): bool
    {
        return is_string($path)
            && str_starts_with($path, 'profile-photos/')
            && ! str_contains($path, '..');
    }
}
