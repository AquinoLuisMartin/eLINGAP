@extends('layouts.auth')
@section('title', 'Forgot password')
@section('content')
<p class="mb-5 text-sm text-slate-600">Enter your assigned username. Your OSCA Administrator must personally verify your identity before issuing a recovery code. Email, SMS and OTP are not required.</p>
<form method="POST" action="{{ route('password.request.store') }}" class="space-y-4">
    @csrf
    <label class="field-label">Username<input name="username" type="text" value="{{ is_string(old('username')) ? old('username') : '' }}" autocomplete="username" autocapitalize="none" maxlength="100" required></label>
    <x-button type="submit">Submit request</x-button>
</form>
<a href="{{ route('password.reset') }}" class="mt-5 inline-block text-sm text-blue-700 underline">Already have a recovery code?</a>
@endsection
