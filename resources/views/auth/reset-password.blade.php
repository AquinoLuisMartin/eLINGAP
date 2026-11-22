@extends('layouts.auth')
@section('title', 'Complete password recovery')
@section('description', 'Enter the single-use code your administrator gave you. It expires 15 minutes after approval.')
@section('content')
@php($inputClass = 'min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-base text-osca-ink placeholder:text-slate-400 focus:border-osca-primary focus:outline-2 focus:outline-offset-2 focus:outline-osca-primary aria-[invalid=true]:border-red-600')

<form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4">
    @csrf
    <div class="flex flex-col gap-1.5">
        <label for="employee-id" class="text-sm font-medium text-osca-ink">Employee ID <span class="field-required" aria-hidden="true">*</span></label>
        <input id="employee-id" name="employee_id" type="text" value="{{ is_string(old('employee_id')) ? old('employee_id') : '' }}" autocomplete="username" minlength="9" maxlength="9" pattern="[0-9]{4}-[0-9]{4}" placeholder="2026-0001" autocapitalize="none" spellcheck="false" required @error('employee_id') aria-invalid="true" aria-describedby="auth-feedback" @enderror class="{{ $inputClass }}">
    </div>
    <div class="flex flex-col gap-1.5">
        <label for="recovery-code" class="text-sm font-medium text-osca-ink">Recovery code <span class="field-required" aria-hidden="true">*</span></label>
        <input id="recovery-code" name="recovery_code" type="text" autocomplete="off" autocapitalize="none" spellcheck="false" maxlength="43" required @error('recovery_code') aria-invalid="true" aria-describedby="auth-feedback" @enderror class="{{ $inputClass }}">
    </div>
    @foreach (['password' => 'New password', 'password_confirmation' => 'Confirm new password'] as $field => $label)
        <div class="flex flex-col gap-1.5" x-data="{ visible: false }">
            <label for="{{ $field }}" class="text-sm font-medium text-osca-ink">{{ $label }} <span class="field-required" aria-hidden="true">*</span></label>
            <div class="relative">
                <input id="{{ $field }}" name="{{ $field }}" type="password" :type="visible ? 'text' : 'password'" autocomplete="new-password" minlength="8" required @error($field) aria-invalid="true" @enderror @if ($field === 'password' || $errors->has($field)) aria-describedby="{{ trim(($field === 'password' ? 'password-help ' : '').($errors->has($field) ? 'auth-feedback' : '')) }}" @endif class="{{ $inputClass }} pr-20">
                <button type="button" x-cloak @click="visible = !visible" :aria-pressed="visible" aria-controls="{{ $field }}" :aria-label="(visible ? 'Hide ' : 'Show ') + '{{ strtolower($label) }}'" x-text="visible ? 'Hide' : 'Show'" class="absolute inset-y-0 right-0 min-w-16 cursor-pointer rounded-r-lg px-3 text-sm font-semibold text-osca-primary hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary">Show</button>
            </div>
            @if ($field === 'password')
                <p id="password-help" class="text-xs leading-5 text-slate-600">8-15 characters. Do not use your name or employee ID.</p>
            @endif
        </div>
    @endforeach
    <button type="submit" class="inline-flex min-h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-lg bg-osca-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-osca-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary">
        Set new password
        <x-lucide-arrow-right class="size-4" aria-hidden="true" />
    </button>
</form>
@endsection
