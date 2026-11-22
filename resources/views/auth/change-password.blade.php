@extends('layouts.auth')
@section('title', 'Change password')
@section('auth-header')
<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-osca-primary" aria-label="eLINGAP home">
            <img src="{{ asset('images/eLINGAP.png') }}" alt="" class="size-9 object-contain" width="36" height="36">
            <span class="text-base font-semibold tracking-tight text-osca-primary">eLINGAP</span>
        </a>
        <a href="{{ url()->previous(route('dashboard')) }}" class="inline-flex min-h-11 items-center rounded px-2 text-sm font-medium text-slate-600 hover:text-osca-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary" aria-label="Cancel password change">Cancel</a>
    </div>
    <div class="flex flex-col gap-1.5">
        <h1 id="auth-heading" class="text-2xl font-semibold tracking-tight text-osca-ink">Change password</h1>
        <p class="text-sm leading-5 text-slate-600">{{ auth()->user()->must_change_password ? 'Create a personal password to continue to your account.' : 'Enter your current password, then choose a new one.' }}</p>
    </div>
</div>
@endsection
@section('content')
<form method="POST" action="{{ route('password.change.update') }}" class="flex flex-col gap-4">
    @csrf
    @foreach (['current_password' => 'Current password', 'password' => 'New password', 'password_confirmation' => 'Confirm new password'] as $field => $label)
        <div class="flex flex-col gap-1.5" x-data="{ visible: false }">
            <label for="{{ $field }}" class="text-sm font-medium text-osca-ink">{{ $label }} <span class="field-required" aria-hidden="true">*</span><span class="sr-only"> required</span></label>
            <div class="relative">
                <input id="{{ $field }}" name="{{ $field }}" type="password" :type="visible ? 'text' : 'password'" autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}" @if ($field !== 'current_password') minlength="8" @endif required aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}" @if ($field === 'password' || $errors->has($field)) aria-describedby="{{ trim(($field === 'password' ? 'password-help ' : '').($errors->has($field) ? $field.'-error' : '')) }}" @endif class="min-h-12 w-full rounded-md border {{ $errors->has($field) ? 'border-red-600' : 'border-slate-300' }} bg-white py-2.5 pr-20 pl-3 text-base text-osca-ink focus:border-osca-primary focus:outline-2 focus:outline-offset-2 focus:outline-osca-primary">
                <button type="button" x-cloak @click="visible = !visible" :aria-pressed="visible" aria-controls="{{ $field }}" aria-label="Show {{ strtolower($label) }}" :aria-label="(visible ? 'Hide ' : 'Show ') + '{{ strtolower($label) }}'" x-text="visible ? 'Hide' : 'Show'" class="absolute inset-y-0 right-0 min-w-16 cursor-pointer rounded-r-lg px-3 text-sm font-semibold text-osca-primary hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary">Show</button>
            </div>
            @if ($field === 'password')
                <p id="password-help" class="text-xs leading-5 text-slate-600">8-15 characters. Do not use your name or employee ID.</p>
            @endif
            @error($field)<p id="{{ $field }}-error" class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>
    @endforeach
    <aside class="border-t border-slate-200 pt-4" aria-label="Account security notice">
        <p class="text-xs leading-5 text-slate-600">Changing your password signs you out of other sessions and revokes your recovery codes.</p>
    </aside>
    <button type="submit" class="inline-flex min-h-12 w-full cursor-pointer items-center justify-center rounded-md bg-osca-primary px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-osca-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary">
        Change password
    </button>
</form>
@endsection
@section('auth-footer')
<div class="mt-4 flex flex-wrap items-center justify-between gap-3">
    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center rounded text-sm font-medium text-slate-600 hover:text-osca-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary">Back to sign in</a>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="min-h-11 cursor-pointer rounded px-2 text-sm font-semibold text-osca-primary hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary">Sign out</button>
    </form>
</div>
@endsection
