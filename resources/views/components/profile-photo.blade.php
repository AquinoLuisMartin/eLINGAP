@props(['user'])

@if ($user->profile_photo_path)
    <img src="{{ route('profile-photo.show', ['v' => $user->updated_at?->getTimestamp()]) }}" alt="">
@else
    {{ $user->initials }}
@endif
