<div id="login-modal" class="fixed inset-0 z-50 {{ ($showLogin ?? false) || $errors->any() ? '' : 'hidden' }} flex items-center justify-center overflow-y-auto bg-slate-900/45 p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="login-modal-title" aria-describedby="login-modal-description" tabindex="-1">
    <section class="my-auto w-full max-w-md rounded-xl border border-osca-border bg-white p-6 shadow-lg sm:p-8">
        <header class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/eLINGAP.png') }}" alt="eLINGAP" class="size-14 object-contain" width="56" height="56">
                <div>
                    <p class="text-sm text-osca-ink">OSCA Santa Maria</p>
                    <p class="mt-1 text-xs text-osca-body">Senior citizen services</p>
                </div>
            </div>
            <button type="button" data-close-modal="login" class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-osca-body transition-colors hover:bg-osca-muted hover:text-osca-ink" aria-label="Close login dialog">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </header>

        <h2 id="login-modal-title" class="mt-6 text-2xl font-semibold text-osca-ink">Sign in to eLINGAP</h2>
        <p id="login-modal-description" class="mt-2 text-sm leading-relaxed text-osca-body">Use your authorized OSCA account to continue.</p>

        <form id="login-form" class="mt-6 space-y-5" action="{{ route('login') }}" method="post">
            @csrf
            @if ($errors->any())
                <p id="login-error" class="rounded-lg border border-red-200 bg-red-50 px-3 py-3 text-sm text-red-700" role="alert">{{ $errors->first() }}</p>
            @endif

            <div class="space-y-2">
                <label for="login-identity" class="block text-sm font-normal text-osca-ink">Email or username</label>
                <input id="login-identity" name="email" type="text" value="{{ old('email') }}" autocomplete="username" autocapitalize="none" spellcheck="false" data-focus required class="login-input min-h-11 w-full rounded-lg border border-osca-border bg-white px-3 py-2.5 text-base text-osca-ink placeholder:text-slate-400 transition-colors" placeholder="Enter your email or username" @if ($errors->any()) aria-invalid="true" aria-describedby="login-error" @endif>
            </div>

            <div class="space-y-2">
                <label for="login-password" class="block text-sm font-normal text-osca-ink">Password</label>
                <div class="relative">
                    <input id="login-password" name="password" type="password" autocomplete="current-password" required class="login-input min-h-11 w-full rounded-lg border border-osca-border bg-white py-2.5 pl-3 pr-14 text-base text-osca-ink placeholder:text-slate-400 transition-colors" placeholder="Enter your password" @if ($errors->any()) aria-invalid="true" aria-describedby="login-error" @endif>
                    <button type="button" data-toggle-password="login-password" class="absolute inset-y-0 right-1 inline-flex w-11 items-center justify-center rounded-lg text-osca-body transition-colors hover:bg-osca-muted hover:text-osca-ink" aria-label="Show password" aria-controls="login-password" aria-pressed="false">
                        <svg data-eye-icon class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2.06 12.35a1 1 0 0 1 0-.7C3.32 8.07 7.26 5 12 5s8.68 3.07 9.94 6.65a1 1 0 0 1 0 .7C20.68 15.93 16.74 19 12 19s-8.68-3.07-9.94-6.65Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 text-sm">
                <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 text-osca-body">
                    <input type="checkbox" name="remember" class="size-4 rounded border-osca-border accent-osca-primary" @checked(old('remember'))>
                    <span>Remember me</span>
                </label>
                <a href="mailto:osca@santamariabulacan.gov.ph?subject=Password%20Reset" class="inline-flex min-h-11 items-center text-osca-primary underline-offset-4 hover:underline">Forgot password?</a>
            </div>

            <button id="login-submit" type="submit" class="min-h-11 w-full rounded-lg bg-osca-primary px-4 py-3 text-sm font-normal text-white transition-colors hover:bg-osca-primary-dark">
                <span data-login-label>Sign in</span>
                <span data-login-loading class="hidden items-center justify-center gap-2">
                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/></svg>
                    <span>Signing in...</span>
                </span>
            </button>
        </form>

        <p class="mt-6 border-t border-osca-border pt-5 text-sm leading-relaxed text-osca-body">Need an account? Contact the OSCA office or your barangay coordinator.</p>
    </section>
</div>
