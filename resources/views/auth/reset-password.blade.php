@extends('layouts.auth')
@section('title', 'Complete password recovery')
@section('content')
<p class="mb-5 text-sm text-slate-600">Use the single-use code provided privately by your administrator. It expires 15 minutes after approval.</p>
<form method="POST" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    <label class="field-label">Username<input name="username" type="text" value="{{ is_string(old('username')) ? old('username') : '' }}" autocomplete="username" autocapitalize="none" maxlength="100" required></label>
    <label class="field-label">Recovery code<input name="recovery_code" type="text" autocomplete="off" autocapitalize="none" spellcheck="false" maxlength="43" required></label>
    <label class="field-label">New password<input name="password" type="password" autocomplete="new-password" minlength="15" required></label>
    <label class="field-label">Confirm new password<input name="password_confirmation" type="password" autocomplete="new-password" minlength="15" required></label>
    <p class="text-sm text-slate-600">Use 15 to 128 characters. Spaces and special characters are allowed. Choose a password unrelated to your name or username.</p>
    <x-button type="submit">Set new password</x-button>
</form>
@endsection
