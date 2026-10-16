@extends('layouts.auth')
@section('title', 'Forgot password')
@section('content')
@php($emailValue = old('email'))
<p class="mb-5 text-sm text-slate-600">Enter your account email address to request a password reset link. If your account has no email address, contact your administrator.</p>
<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf
    <div>
        <label for="email" class="block text-sm font-medium">Email address</label>
        <input id="email" name="email" type="email" value="{{ is_string($emailValue) ? $emailValue : '' }}" autocomplete="email" maxlength="255" required class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-2 focus:outline-blue-600" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
        @error('email')<p id="email-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <button type="submit" class="min-h-11 w-full rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white focus:outline-2 focus:outline-offset-2 focus:outline-blue-600">Send reset link</button>
</form>
@endsection
