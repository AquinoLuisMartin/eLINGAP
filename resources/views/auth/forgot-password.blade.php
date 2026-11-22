@extends('layouts.auth')
@section('title', 'Forgot password')
@section('heading', 'Forgot password?')
@section('content')
<form method="POST" action="{{ route('password.request.store') }}" class="flex flex-col gap-4">
    @csrf
    <div class="flex flex-col gap-2">
        <label for="employee-id" class="text-sm font-medium text-osca-ink">Employee ID <span class="field-required text-red-700" aria-hidden="true">*</span><span class="sr-only"> required</span></label>
        <input id="employee-id" name="employee_id" type="text" value="{{ is_string(old('employee_id')) ? old('employee_id') : '' }}" autocomplete="username" minlength="9" maxlength="9" pattern="[0-9]{4}-[0-9]{4}" placeholder="2026-0001" autocapitalize="none" spellcheck="false" required @error('employee_id') aria-invalid="true" @enderror class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-base text-osca-ink placeholder:text-slate-400 focus:border-osca-primary focus:outline-2 focus:outline-offset-2 focus:outline-osca-primary">

    </div>
    <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-osca-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-osca-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary">
        Submit request
        <x-lucide-arrow-right class="size-4" aria-hidden="true" />
    </button>
</form>
<aside class="mt-4 flex gap-2.5 rounded-lg border border-blue-100 bg-blue-50/70 p-3" aria-labelledby="recovery-help-heading">
    <x-lucide-check class="mt-0.5 size-5 shrink-0 text-osca-primary" aria-hidden="true" />
    <div class="flex flex-col gap-1">
        <h2 id="recovery-help-heading" class="text-sm font-semibold text-osca-primary">Recovery through your administrator</h2>
        <p class="text-sm leading-5 text-slate-600">An OSCA Administrator must verify your identity before issuing a recovery code.</p>
    </div>
</aside>
<div class="mt-3 flex flex-wrap items-center justify-center gap-x-1.5 text-sm">
    <span class="text-slate-600">Already have a recovery code?</span>
    <a href="{{ route('password.reset') }}" class="inline-flex min-h-11 items-center rounded font-semibold text-osca-primary underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary">Enter recovery code</a>
</div>
@endsection
