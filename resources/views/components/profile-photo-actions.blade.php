@props(['user'])

<div class="profile-photo-actions">
    <label for="profile-photo" class="text-button">Change photo</label>
    @if ($user->profile_photo_path)
        <form method="POST" action="{{ route('profile-photo.destroy') }}" @click.stop>
            @csrf
            @method('DELETE')
            <button type="submit" class="text-button">Remove photo</button>
        </form>
    @endif
</div>
@error('photo')
    <p class="form-error" role="alert">{{ $message }}</p>
@enderror
