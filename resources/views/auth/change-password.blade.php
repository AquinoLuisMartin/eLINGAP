@extends('layouts.auth')
@section('title', 'Change password')
@section('card-actions')
<a href="{{ url()->previous(route('dashboard')) }}" class="absolute top-4 right-4 inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700" aria-label="Cancel password change" title="Cancel password change">
    <span aria-hidden="true"><x-admin.icon name="x" size="20" /></span>
</a>
@endsection
@section('content')
<p class="mb-5 text-sm text-slate-600">{{ auth()->user()->must_change_password ? 'Replace your temporary password before accessing the system.' : 'Verify your current password to set a new personal password.' }} Other sessions and recovery codes will be revoked.</p>
<form method="POST" action="{{ route('password.change.update') }}" class="space-y-4">
    @csrf
    <label class="field-label">Current password<input name="current_password" type="password" autocomplete="current-password" required></label>
    <label class="field-label">New password<input name="password" type="password" autocomplete="new-password" minlength="15" required></label>
    <label class="field-label">Confirm new password<input name="password_confirmation" type="password" autocomplete="new-password" minlength="15" required></label>
    <p class="text-sm text-slate-600">Use 15 to 128 characters. Spaces and special characters are allowed. Choose a password unrelated to your name or employee ID.</p>
    <x-button type="submit">Change password</x-button>
</form>
<form method="POST" action="{{ route('logout') }}" class="mt-5">@csrf<button type="submit" class="text-sm text-blue-700 underline">Sign out</button></form>
@endsection
