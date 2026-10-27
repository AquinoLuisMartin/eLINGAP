@extends('layouts.auth')
@section('title', 'Change password')
@section('content')
<p class="mb-5 text-sm text-slate-600">{{ auth()->user()->must_change_password ? 'Replace your temporary password before accessing the system.' : 'Verify your current password to set a new personal password.' }} Other sessions and recovery codes will be revoked.</p>
<form method="POST" action="{{ route('password.change.update') }}" class="space-y-4">
    @csrf
    <label class="field-label">Current password<input name="current_password" type="password" autocomplete="current-password" required></label>
    <label class="field-label">New password<input name="password" type="password" autocomplete="new-password" minlength="15" required></label>
    <label class="field-label">Confirm new password<input name="password_confirmation" type="password" autocomplete="new-password" minlength="15" required></label>
    <p class="text-sm text-slate-600">Use 15 to 128 characters. Spaces and special characters are allowed. Choose a password unrelated to your name or username.</p>
    <x-button type="submit">Change password</x-button>
</form>
<form method="POST" action="{{ route('logout') }}" class="mt-5">@csrf<button type="submit" class="text-sm text-blue-700 underline">Sign out</button></form>
@endsection
