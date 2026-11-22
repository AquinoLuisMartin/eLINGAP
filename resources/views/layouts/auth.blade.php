<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | eLINGAP</title>
    <x-favicon />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900">
    <main class="flex min-h-screen items-center justify-center p-4">
        <section class="relative w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="auth-heading">
            @yield('card-actions')
            @hasSection('auth-header')
                @yield('auth-header')
            @else
                <header class="flex flex-col items-center gap-3 text-center">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-osca-primary" aria-label="eLINGAP home">
                        <img src="{{ asset('images/eLINGAP.png') }}" alt="" class="size-11 object-contain" width="44" height="44">
                        <span class="text-lg font-semibold tracking-tight text-osca-primary">eLINGAP</span>
                    </a>
                    <div class="flex flex-col gap-1">
                        <h1 id="auth-heading" class="text-2xl font-semibold tracking-tight text-osca-ink">@yield('heading', View::getSection('title'))</h1>
                        @hasSection('description')
                            <p class="text-sm leading-5 text-slate-600">@yield('description')</p>
                        @endif
                    </div>
                </header>
            @endif
            <div id="auth-feedback" class="mt-4"><x-alert /></div>
            @yield('content')
            @hasSection('auth-footer')
                @yield('auth-footer')
            @else
                <div class="mt-3 border-t border-slate-100 pt-2 text-center">
                    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center gap-2 rounded text-sm font-medium text-slate-600 hover:text-osca-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary">
                        <x-lucide-arrow-left class="size-4" aria-hidden="true" />
                        Back to sign in
                    </a>
                </div>
            @endif
        </section>
    </main>
</body>
</html>
