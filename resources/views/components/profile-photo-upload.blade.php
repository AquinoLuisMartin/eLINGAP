@props(['user'])

<form method="POST" action="{{ route('profile-photo.update') }}" enctype="multipart/form-data" class="profile-photo-form" @click.stop>
    @csrf
    <label class="profile-menu-avatar" for="profile-photo" aria-label="Upload profile photo">
        <span class="profile-photo-face"><x-profile-photo :user="$user" /></span>
    </label>
    <input id="profile-photo" class="profile-photo-input" type="file" name="photo" accept="image/jpeg,image/png,image/webp" aria-label="Upload profile photo" onchange="this.form.requestSubmit()">
</form>
