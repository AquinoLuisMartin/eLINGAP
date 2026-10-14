<div id="login-modal" class="fixed inset-0 z-50 {{ ($showLogin ?? false) || $errors->any() ? '' : 'hidden' }} flex items-center justify-center overflow-y-auto bg-white px-6 py-10" role="dialog" aria-modal="true" aria-labelledby="login-modal-title" aria-describedby="login-modal-description" tabindex="-1" x-data="{ recoveryHelp: false }">
    <section class="my-auto w-full max-w-sm py-6">
        <header class="mb-6 flex justify-center">
            <div class="flex items-center gap-2.5">
                <img src="{{ asset('images/eLINGAP.png') }}" alt="eLINGAP" class="size-12 rounded-lg bg-white object-contain" width="48" height="48">
                <div>
                    <p class="text-sm font-medium text-osca-ink">eLINGAP</p>
                    <p class="mt-0.5 text-xs text-osca-body">OSCA Santa Maria</p>
                </div>
            </div>
            <button type="button" data-close-modal="login" class="absolute top-4 right-4 inline-flex size-10 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-osca-muted hover:text-osca-ink sm:top-6 sm:right-6" aria-label="Close login dialog">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </header>

        <h2 id="login-modal-title" class="text-center text-2xl font-semibold tracking-tight text-osca-ink">Sign in to eLINGAP</h2>
        <p id="login-modal-description" class="mt-2 text-center text-sm text-osca-body">Access your OSCA staff workspace.</p>

        <form id="login-form" class="mt-6 space-y-4" action="{{ route('login') }}" method="post">
            @csrf
            @if ($errors->any())
                <p id="login-error" class="rounded-lg border border-red-200 bg-red-50 px-3 py-3 text-sm text-red-700" role="alert">{{ $errors->first() }}</p>
            @endif

            <div class="space-y-2">
                <label for="login-identity" class="block text-sm font-medium text-osca-ink">Username</label>
                <input id="login-identity" name="email" type="text" value="{{ old('email') }}" autocomplete="username" autocapitalize="none" spellcheck="false" data-focus required class="login-input min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-base text-osca-ink placeholder:text-sm placeholder:text-slate-400 transition-colors hover:border-slate-400" placeholder="Enter your username" @error('email') aria-invalid="true" aria-describedby="login-error" @enderror>
            </div>

            <div class="space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <label for="login-password" class="text-sm font-medium text-osca-ink">Password</label>
                    <button type="button" @click="recoveryHelp = !recoveryHelp" :aria-expanded="recoveryHelp" aria-controls="login-recovery-help" class="rounded py-1 text-sm text-osca-primary hover:text-osca-primary-dark">Forgot password?</button>
                </div>
                <div class="relative">
                    <input id="login-password" name="password" type="password" autocomplete="current-password" required class="login-input min-h-11 w-full rounded-lg border border-slate-300 bg-white py-2.5 pr-12 pl-3 text-base text-osca-ink placeholder:text-sm placeholder:text-slate-400 transition-colors hover:border-slate-400" placeholder="Enter your password" @error('password') aria-invalid="true" aria-describedby="login-error" @enderror>
                    <button type="button" data-toggle-password="login-password" class="absolute inset-y-0 right-1 inline-flex w-11 items-center justify-center rounded-lg text-osca-body transition-colors hover:bg-osca-muted hover:text-osca-ink" aria-label="Show password" aria-controls="login-password" aria-pressed="false">
                        <svg data-eye-icon class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2.06 12.35a1 1 0 0 1 0-.7C3.32 8.07 7.26 5 12 5s8.68 3.07 9.94 6.65a1 1 0 0 1 0 .7C20.68 15.93 16.74 19 12 19s-8.68-3.07-9.94-6.65Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 text-sm">
                <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 text-osca-body">
                    <input type="checkbox" name="remember" value="1" class="size-4 rounded border-osca-border accent-osca-primary" @checked(old('remember'))>
                    <span>Remember me</span>
                </label>
            </div>
            <p id="login-recovery-help" x-show="recoveryHelp" x-cloak class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-2.5 text-sm text-osca-primary">Ask your OSCA administrator to reset your account password.</p>

            <button id="login-submit" type="submit" class="flex min-h-11 w-full items-center justify-center rounded-lg bg-osca-primary px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-osca-primary-dark disabled:cursor-wait disabled:opacity-70">
                <span data-login-label>Sign in</span>
                <span data-login-loading role="status" class="hidden items-center justify-center gap-2">
                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/></svg>
                    <span>Signing in...</span>
                </span>
            </button>
        </form>

        <p class="mt-6 text-center text-sm leading-relaxed text-osca-body">Need access? Contact the OSCA office or your barangay coordinator.</p>
    </section>
</div>
