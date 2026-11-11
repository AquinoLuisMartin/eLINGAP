@extends('layouts.auth')
@section('title', 'Complete password recovery')
@section('content')
<p class="mb-5 text-sm text-slate-600">Use the single-use code provided privately by your administrator. It expires 15 minutes after approval.</p>
<form method="POST" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    <label class="field-label">Employee ID<input name="employee_id" type="text" value="{{ is_string(old('employee_id')) ? old('employee_id') : '' }}" autocomplete="username" minlength="9" maxlength="9" pattern="[0-9]{4}-[0-9]{4}" placeholder="2026-0001" autocapitalize="none" required></label>
    <label class="field-label">Recovery code<input name="recovery_code" type="text" autocomplete="off" autocapitalize="none" spellcheck="false" maxlength="43" required></label>
    <label class="field-label">New password<input name="password" type="password" autocomplete="new-password" minlength="8" required></label>
    <label class="field-label">Confirm new password<input name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required></label>
    <p class="text-sm text-slate-600">Use 8 to 15 characters. Spaces and special characters are allowed. Choose a password unrelated to your name or employee ID.</p>
    <x-button type="submit">Set new password</x-button>
</form>
@endsection
