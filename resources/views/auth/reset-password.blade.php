@extends('layouts.auth')
@section('title', 'Reset password')
@section('content')
@php($emailValue = old('email', $email))
<form method="POST" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div>
        <label for="email" class="block text-sm font-medium">Email address</label>
        <input id="email" name="email" type="email" value="{{ is_string($emailValue) ? $emailValue : '' }}" autocomplete="email" maxlength="255" required class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-2 focus:outline-blue-600" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
        @error('email')<p id="email-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <p id="password-help" class="text-sm text-slate-600">Use at least 12 characters and no more than 72 bytes. Some characters use more than one byte.</p>
    <div>
        <label for="password" class="block text-sm font-medium">New password</label>
        <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required aria-describedby="password-help @error('password') password-error @enderror" @error('password') aria-invalid="true" @enderror class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-2 focus:outline-blue-600">
        @error('password')<p id="password-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="password_confirmation" class="block text-sm font-medium">Confirm new password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-2 focus:outline-blue-600">
    </div>
    <button type="submit" class="min-h-11 w-full rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white focus:outline-2 focus:outline-offset-2 focus:outline-blue-600">Reset password</button>
</form>
@endsection
