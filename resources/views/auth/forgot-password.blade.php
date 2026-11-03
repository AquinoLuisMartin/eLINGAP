@extends('layouts.auth')
@section('title', 'Forgot password')
@section('auth-header')
<div class="flex flex-col items-center gap-5 text-center">
    <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-osca-primary" aria-label="eLINGAP home">
        <img src="{{ asset('images/eLINGAP.png') }}" alt="" class="size-11 object-contain" width="44" height="44">
        <span class="text-lg font-semibold tracking-tight text-osca-primary">eLINGAP</span>
    </a>
    <div class="flex flex-col gap-2">
        <h1 id="auth-heading" class="text-2xl font-semibold tracking-tight text-osca-ink">Forgot password?</h1>
        <p class="text-sm leading-6 text-slate-600">Enter your employee ID to request help recovering your account.</p>
    </div>
</div>
@endsection
@section('content')
<form method="POST" action="{{ route('password.request.store') }}" class="flex flex-col gap-5">
    @csrf
    <div class="flex flex-col gap-2">
        <label for="employee-id" class="text-sm font-medium text-osca-ink">Employee ID <span class="text-red-700" aria-hidden="true">*</span><span class="sr-only"> required</span></label>
        <input id="employee-id" name="employee_id" type="text" value="{{ is_string(old('employee_id')) ? old('employee_id') : '' }}" autocomplete="username" minlength="9" maxlength="9" pattern="[0-9]{4}-[0-9]{4}" placeholder="2026-0001" autocapitalize="none" spellcheck="false" required aria-describedby="employee-id-help" @error('employee_id') aria-invalid="true" @enderror class="min-h-12 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-base text-osca-ink placeholder:text-slate-400 focus:border-osca-primary focus:outline-2 focus:outline-offset-2 focus:outline-osca-primary">
        <p id="employee-id-help" class="text-xs text-slate-500">Use the employee ID assigned to you by OSCA.</p>
    </div>
    <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-lg bg-osca-primary px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-osca-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary">
        Submit request
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
    </button>
</form>
<aside class="mt-6 flex gap-3 rounded-lg border border-blue-100 bg-blue-50/70 p-4" aria-labelledby="recovery-help-heading">
    <svg class="mt-0.5 size-5 shrink-0 text-osca-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="m12 3 8 4v5c0 5-8 9-8 9s-8-4-8-9V7l8-4Z"/><path d="m8 12 3 3 5-5"/></svg>
    <div class="flex flex-col gap-1">
        <h2 id="recovery-help-heading" class="text-sm font-semibold text-osca-primary">Recovery through your administrator</h2>
        <p class="text-sm leading-6 text-slate-600">Your OSCA Administrator must personally verify your identity before issuing a recovery code. No email, SMS or OTP is needed.</p>
    </div>
</aside>
<div class="mt-6 flex flex-col items-center gap-1 text-sm">
    <span class="text-slate-600">Already have a recovery code?</span>
    <a href="{{ route('password.reset') }}" class="rounded py-1 font-semibold text-osca-primary underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary">Enter recovery code</a>
</div>
@endsection
@section('auth-footer')
<div class="mt-5 border-t border-slate-100 pt-4 text-center">
    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center gap-2 rounded text-sm font-medium text-slate-600 hover:text-osca-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5m6-6-6 6 6 6"/></svg>
        Back to sign in
    </a>
</div>
@endsection
