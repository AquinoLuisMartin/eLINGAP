<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateAccountRequest;
use App\Services\Auth\ProfilePhotos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ProfilePhotoController extends Controller
{
    public function show(Request $request): BinaryFileResponse
    {
        $path = $request->user()->profile_photo_path;
        abort_unless($this->ownedPhoto($path), 404);

        $mime = Storage::disk('local')->mimeType($path);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

        $response = response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="profile-photo"',
        ]);
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
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        if ($previous !== $path) {
            $this->deleteStoredPhoto($previous);
        }

        return back()->with('status', 'Profile photo updated.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $previous = $user->profile_photo_path;
        $user->update(['profile_photo_path' => null]);
        $this->deleteStoredPhoto($previous);

        return back()->with('status', 'Profile photo removed.');
    }

    private function ownedPhoto(mixed $path): bool
    {
        return is_string($path)
            && str_starts_with($path, 'profile-photos/')
            && ! str_contains($path, '..')
            && Storage::disk('local')->exists($path);
    }

    private function deleteStoredPhoto(mixed $path): void
    {
        if ($this->ownedPhoto($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
