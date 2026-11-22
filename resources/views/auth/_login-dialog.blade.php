<div id="login-modal" class="fixed inset-0 z-50 {{ ($showLogin ?? false) || $errors->any() ? '' : 'hidden' }} flex items-center justify-center overflow-y-auto bg-white px-6 py-10" role="dialog" aria-modal="true" aria-labelledby="login-modal-title" tabindex="-1" x-data="{ recoveryHelp: false }">
    <section class="my-auto w-full max-w-sm py-6">
        <header class="mb-6 flex justify-center">
            <div class="flex items-center gap-2.5">
                <img src="{{ asset('images/eLINGAP.png') }}" alt="eLINGAP" class="size-12 rounded-lg bg-white object-contain" width="48" height="48">
                <div>
                    <p class="text-sm font-medium text-osca-ink">eLINGAP</p>
                    <p class="mt-0.5 text-xs text-osca-body">{{ $officeDetails['office_name'] ?? 'Office of the Senior Citizen Affairs' }}</p>
                </div>
            </div>
            <button type="button" data-close-modal="login" class="absolute top-4 right-4 inline-flex size-10 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-osca-muted hover:text-osca-ink sm:top-6 sm:right-6" aria-label="Close login dialog">
                <x-lucide-x class="size-5" aria-hidden="true" />
            </button>
        </header>

        <h2 id="login-modal-title" class="text-center text-2xl font-semibold tracking-tight text-osca-ink">Sign in to eLINGAP</h2>

        <form id="login-form" class="mt-6 space-y-4" action="{{ route('login') }}" method="post" data-validation-errors="{{ json_encode($errors->messages()) }}">
            @csrf
            @if ($errors->any())
                <p id="login-error" class="rounded-lg border border-red-200 bg-red-50 px-3 py-3 text-sm text-red-700" role="alert">{{ $errors->first() }}</p>
            @endif

            <div class="space-y-2">
                <label for="login-identity" class="block text-sm font-medium text-osca-ink">Employee ID</label>
                <input id="login-identity" name="employee_id" type="text" value="{{ is_string(old('employee_id')) ? old('employee_id') : '' }}" autocomplete="username" minlength="9" maxlength="9" pattern="[0-9]{4}-[0-9]{4}" placeholder="2026-0001" autocapitalize="none" spellcheck="false" data-focus required class="login-input min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-base text-osca-ink placeholder:text-sm placeholder:text-slate-400 transition-colors hover:border-slate-400" @error('employee_id') aria-invalid="true" aria-describedby="login-error" @enderror>
            </div>

            <div class="flex flex-col gap-2">
                <label for="login-password" class="text-sm font-medium text-osca-ink">Password</label>
                <div class="relative">
                    <input id="login-password" name="password" type="password" autocomplete="current-password" required class="login-input min-h-11 w-full rounded-lg border border-slate-300 bg-white py-2.5 pr-12 pl-3 text-base text-osca-ink placeholder:text-sm placeholder:text-slate-400 transition-colors hover:border-slate-400" placeholder="Enter your password" @error('password') aria-invalid="true" aria-describedby="login-error" @enderror>
                    <button type="button" data-toggle-password="login-password" class="absolute inset-y-1 right-1 inline-flex w-10 cursor-pointer items-center justify-center rounded-md bg-transparent text-slate-500 transition-colors hover:text-osca-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary" aria-label="Show password" aria-controls="login-password" aria-pressed="false">
                        <x-lucide-eye data-eye-icon class="size-5 shrink-0" aria-hidden="true" />
                        <x-lucide-eye-off data-eye-off-icon class="hidden size-5 shrink-0" aria-hidden="true" />
                    </button>
                </div>
                <a href="{{ route('password.request') }}" class="self-end rounded py-1 text-sm text-osca-primary hover:text-osca-primary-dark">Forgot password?</a>
            </div>

            <div class="flex flex-col gap-2">
                <div id="login-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
                <p id="login-verification-status" class="rounded-md px-3 py-2 text-sm leading-5 text-osca-body data-[state=success]:bg-emerald-50 data-[state=success]:font-medium data-[state=success]:text-emerald-800" role="status" aria-live="polite">Loading security verification...</p>
                <button id="login-verification-retry" class="self-start rounded px-3 py-1 text-sm font-medium text-osca-primary hover:underline" type="button" hidden>Retry verification</button>
            </div>

            <button id="login-submit" type="submit" class="flex min-h-11 w-full items-center justify-center rounded-lg bg-osca-primary px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-osca-primary-dark disabled:cursor-wait disabled:opacity-70">
                <span data-login-label>Sign in</span>
                <span data-login-loading role="status" class="hidden items-center justify-center gap-2">
                    <x-lucide-loader-circle class="size-4 animate-spin" aria-hidden="true" />
                    <span>Signing in...</span>
                </span>
            </button>
        </form>
    </section>
</div>
