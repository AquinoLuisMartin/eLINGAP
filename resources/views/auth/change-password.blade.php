@extends('layouts.auth')
@section('title', 'Change password')
@section('card-actions')
<a href="{{ url()->previous(route('dashboard')) }}" class="absolute top-4 right-4 inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700" aria-label="Cancel password change" title="Cancel password change">
    <span aria-hidden="true"><x-admin.icon name="x" size="20" /></span>
</a>
@endsection
@section('auth-header')
<div class="flex flex-col items-center gap-5 text-center">
    <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-osca-primary" aria-label="eLINGAP home">
        <img src="{{ asset('images/eLINGAP.png') }}" alt="" class="size-11 object-contain" width="44" height="44">
        <span class="text-lg font-semibold tracking-tight text-osca-primary">eLINGAP</span>
    </a>
    <div class="flex flex-col gap-2">
        <h1 id="auth-heading" class="text-2xl font-semibold tracking-tight text-osca-ink">Change password</h1>
        <p class="text-sm leading-6 text-slate-600">{{ auth()->user()->must_change_password ? 'Set a personal password to secure your account before accessing the system.' : 'Verify your current password to set a new personal password.' }}</p>
    </div>
</div>
@endsection
@section('content')
<form method="POST" action="{{ route('password.change.update') }}" class="flex flex-col gap-5">
    @csrf
    @foreach (['current_password' => 'Current password', 'password' => 'New password', 'password_confirmation' => 'Confirm new password'] as $field => $label)
        <div class="ui-field" x-data="{ visible: false }">
            <label for="{{ $field }}" class="text-sm font-medium text-osca-ink">{{ $label }} <span class="field-required" aria-hidden="true">*</span><span class="sr-only"> required</span></label>
            <div class="relative">
                <input id="{{ $field }}" name="{{ $field }}" type="password" :type="visible ? 'text' : 'password'" autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}" @if ($field !== 'current_password') minlength="15" @endif required aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}" aria-describedby="{{ $field }}-help{{ $errors->has($field) ? ' '.$field.'-error' : '' }}" class="min-h-12 w-full rounded-lg border border-slate-300 bg-white py-3 pr-20 pl-3.5 text-base text-osca-ink focus:border-osca-primary focus:outline-2 focus:outline-offset-2 focus:outline-osca-primary">
                <button type="button" x-cloak @click="visible = !visible" :aria-pressed="visible" aria-controls="{{ $field }}" aria-label="Show {{ strtolower($label) }}" :aria-label="(visible ? 'Hide ' : 'Show ') + '{{ strtolower($label) }}'" x-text="visible ? 'Hide' : 'Show'" class="absolute inset-y-0 right-0 min-w-16 cursor-pointer rounded-r-lg px-3 text-sm font-semibold text-osca-primary hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary">Show</button>
            </div>
            <p id="{{ $field }}-help" class="text-xs leading-5 text-slate-500">{{ match ($field) {
                'current_password' => 'Enter the password you currently use to sign in.',
                'password' => 'Use 15 to 128 characters. Spaces and special characters are allowed. Choose a password unrelated to your name or employee ID.',
                default => 'Enter your new password again to confirm.',
            } }}</p>
            @error($field)<p id="{{ $field }}-error" class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>
    @endforeach
    <aside class="flex gap-3 rounded-lg border border-blue-100 bg-blue-50/70 p-4" aria-label="Account security notice">
        <span class="mt-0.5 shrink-0 text-osca-primary" aria-hidden="true"><x-admin.icon name="shield-check" size="20" /></span>
        <p class="text-sm leading-6 text-slate-600">For your security, other sessions and recovery codes will be revoked when you change your password.</p>
    </aside>
    <button type="submit" class="inline-flex min-h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-lg bg-osca-primary px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-osca-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary">
        Change password
        <span aria-hidden="true"><x-admin.icon name="chevron-right" size="18" /></span>
    </button>
</form>
@endsection
@section('auth-footer')
<div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center rounded text-sm font-medium text-slate-600 hover:text-osca-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary">Back to sign in</a>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="min-h-11 cursor-pointer rounded px-2 text-sm font-semibold text-osca-primary hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary">Sign out</button>
    </form>
</div>
@endsection
