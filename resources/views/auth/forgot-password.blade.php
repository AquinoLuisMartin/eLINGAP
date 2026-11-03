@extends('layouts.auth')
@section('title', 'Forgot password')
@section('content')
<p class="mb-5 text-sm text-slate-600">Enter your assigned employee ID. Your OSCA Administrator must personally verify your identity before issuing a recovery code. Email, SMS and OTP are not required.</p>
<form method="POST" action="{{ route('password.request.store') }}" class="space-y-4">
    @csrf
    <label class="field-label">Employee ID<input name="employee_id" type="text" value="{{ is_string(old('employee_id')) ? old('employee_id') : '' }}" autocomplete="username" minlength="9" maxlength="9" pattern="[0-9]{4}-[0-9]{4}" placeholder="2026-0001" autocapitalize="none" required></label>
    <x-button type="submit">Submit request</x-button>
</form>
<a href="{{ route('password.reset') }}" class="mt-5 inline-block text-sm text-blue-700 underline">Already have a recovery code?</a>
@endsection
