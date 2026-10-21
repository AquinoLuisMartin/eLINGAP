<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth motion-reduce:scroll-auto">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Explore senior citizen programs and plan your visit to OSCA Santa Maria, Bulacan.">
    <title>eLINGAP | OSCA Santa Maria</title>
    <x-favicon />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="landing-page bg-white font-sans text-slate-600 antialiased">
    <a href="#landing-main" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-3 focus:text-slate-900">Skip to main content</a>

    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white">
        <div class="mx-auto flex min-h-20 max-w-7xl items-center justify-between gap-3 px-4 sm:gap-6 sm:px-6 lg:px-8">
            <a href="#hero" data-view="home" class="flex shrink-0 items-center gap-2 rounded-lg sm:gap-3" aria-label="eLINGAP home">
                <img src="{{ asset('images/eLINGAP.png') }}" alt="" class="size-10 object-contain sm:size-12" width="48" height="48">
                <div><span class="block text-lg font-bold tracking-tight text-slate-900 sm:text-xl">eLINGAP</span><span class="block text-[11px] text-slate-500 sm:text-xs">OSCA Santa Maria</span></div>
            </a>
            <nav class="ml-auto hidden items-center gap-8 text-sm font-medium lg:flex" aria-label="Main menu">
                <a href="#hero" data-view="home" data-view-link class="py-3 hover:text-slate-900 aria-[current=page]:text-slate-900">Home</a>
                <a href="#programs" data-home-section class="py-3 hover:text-slate-900">Programs</a>
                <a href="#about-view" data-view="about" data-view-link class="py-3 hover:text-slate-900 aria-[current=page]:text-slate-900">About</a>
            </nav>
            <div class="flex items-center gap-2 sm:gap-4">
                <button type="button" data-open-modal="login" aria-haspopup="dialog" aria-controls="login-modal" class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-lg bg-osca-primary px-3 py-2.5 text-xs font-semibold text-white transition-colors hover:bg-osca-primary-dark sm:px-5 sm:text-sm">Staff sign in</button>
                <button type="button" id="mobile-menu-btn" class="inline-flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-slate-200 text-slate-900 hover:bg-slate-50 lg:hidden" aria-label="Toggle menu" aria-controls="mobile-menu" aria-expanded="false">
                    <svg id="menu-icon-bars" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg id="menu-icon-close" class="hidden size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M6 18 18 6"/></svg>
                </button>
            </div>
        </div>
        <nav id="mobile-menu" class="hidden max-h-[calc(100dvh-5rem)] overflow-y-auto border-t border-slate-100 bg-white px-4 py-3 lg:hidden" aria-label="Mobile menu">
            @foreach (['hero' => 'Home', 'programs' => 'Programs', 'about-view' => 'About'] as $target => $label)
                <a href="#{{ $target }}" @if ($target === 'hero' || $target === 'about-view') data-view="{{ $target === 'hero' ? 'home' : 'about' }}" data-view-link @else data-home-section @endif class="block rounded-lg px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50 aria-[current=page]:bg-slate-100 aria-[current=page]:text-slate-900">{{ $label }}</a>
            @endforeach
        </nav>
    </header>

    <main id="landing-main" tabindex="-1">
        <div id="landing-view">
            <section id="hero" class="border-b border-slate-200 bg-slate-50" aria-labelledby="hero-title">
                <div class="mx-auto grid max-w-7xl items-stretch gap-8 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-2 lg:gap-12 lg:px-8">
                    <div class="flex flex-col items-start justify-center gap-6 py-4">
                        <h1 id="hero-title" class="max-w-2xl text-4xl leading-[1.15] font-semibold tracking-tight text-slate-900 sm:text-5xl">Senior citizen programs<br><span class="text-slate-900">in Santa Maria</span></h1>
                        <p class="max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg">Find senior citizen programs, learn about available assistance, and plan your visit to the Office of the Senior Citizens Affairs in Santa Maria, Bulacan.</p>
                    </div>
                    <aside class="min-w-0" aria-label="Your OSCA office">
                        <div class="relative flex h-full flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                            <div class="flex items-center justify-center gap-12 border-b border-slate-100 px-6 py-6 sm:px-8">
                                <img src="{{ asset('images/osca-sta-maria-logo.png') }}" alt="OSCA Santa Maria, Bulacan logo" class="size-24 object-contain" width="96" height="96">
                                <img src="{{ asset('images/lgu-seal.png') }}" alt="Municipality of Santa Maria seal" class="size-24 object-contain" width="96" height="96">
                            </div>
                            <div class="flex flex-1 flex-col gap-5 p-6 sm:p-8">
                                <h2 class="text-2xl leading-snug font-semibold tracking-tight text-slate-900">Office of the Senior Citizens Affairs</h2>
                                <p class="text-sm leading-relaxed">Program information and registration guidance, with assistance from your municipal OSCA team.</p>
                                <p class="border-t border-slate-100 pt-5 text-sm font-medium text-slate-700">Santa Maria Municipal Hall<br><span class="font-normal text-slate-500">Poblacion, Santa Maria</span></p>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>

            @include('public.programs')

            <section id="office-contact" class="border-t border-slate-200 bg-slate-50 py-14 sm:py-20" aria-labelledby="visit-title">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-8 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between lg:gap-12">
                        <h2 id="visit-title" class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">Visit the office</h2>
                        <p class="max-w-md text-sm leading-relaxed">Have a question about registration or a program? Our staff can guide you through the requirements and next steps.</p>
                    </div>
                    <div class="grid items-stretch gap-6 lg:grid-cols-2">
                        <div class="flex h-full flex-col rounded-lg border border-slate-200 bg-white p-6 sm:p-8">
                            <h3 class="text-xl font-semibold tracking-tight text-slate-900">Address &amp; location</h3>
                            <dl class="mt-6 flex flex-col gap-6 text-sm">
                                <div><dt class="font-semibold text-slate-800">Address</dt><dd class="mt-2 max-w-xl leading-relaxed">{{ $publicOffice['office_address'] }}</dd></div>
                                <div><dt class="font-semibold text-slate-800">Contact number</dt><dd class="mt-2 break-words text-lg font-semibold text-slate-900">{{ $publicOffice['support_phone'] }}</dd></div>
                                @if (!empty($publicOffice['support_email']))<div><dt class="font-semibold text-slate-800">Email</dt><dd class="mt-2 break-words">{{ $publicOffice['support_email'] }}</dd></div>@endif
                            </dl>
                            <a href="https://www.google.com/maps/search/?api=1&amp;query=Santa+Maria+Municipal+Hall+Poblacion+Santa+Maria+Bulacan" target="_blank" rel="noopener noreferrer" class="mt-6 inline-flex min-h-11 items-center self-start rounded-lg border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-900 hover:border-slate-400 hover:bg-slate-50">View location on Google Maps<span class="sr-only"> (opens in a new tab)</span></a>
                        </div>
                        <div class="flex h-full flex-col rounded-lg border border-slate-200 bg-white p-6 text-slate-900 sm:p-8">
                            <h3 class="text-xl font-semibold tracking-tight">Operating hours</h3>
                            <dl class="mt-6 flex flex-col gap-6 text-sm">
                                <div class="border-b border-slate-100 pb-6"><dt class="font-semibold text-slate-800">Monday to Friday</dt><dd class="mt-2 text-lg font-semibold leading-relaxed text-slate-900">{{ $publicOffice['office_hours'] }}</dd></div>
                                <div><dt class="font-semibold text-slate-800">Saturday &amp; Sunday</dt><dd class="mt-2 text-sm text-slate-600">Closed</dd></div>
                            </dl>
                            <p class="mt-auto border-t border-slate-100 pt-6 text-sm leading-relaxed text-slate-600">Please call ahead for holiday schedules and any changes to office hours.</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <x-about-elingap />
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-8 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <div><p class="font-semibold text-slate-900">eLINGAP <span class="font-normal text-slate-500">&middot; OSCA Santa Maria</span></p><p class="mt-2 text-xs text-slate-500">&copy; {{ date('Y') }} Municipality of Santa Maria, Bulacan. All rights reserved.</p></div>
            <nav class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm" aria-label="Footer navigation">
                <a href="#programs" data-home-section class="inline-flex min-h-11 items-center rounded hover:text-slate-900">Programs</a>
                <a href="#about-view" data-view="about" class="inline-flex min-h-11 items-center rounded hover:text-slate-900">About eLINGAP</a>
                <button type="button" data-back-to-top class="inline-flex min-h-11 cursor-pointer items-center rounded text-slate-900 hover:underline">Back to top</button>
            </nav>
        </div>
    </footer>

    @include('auth._login-dialog', ['showLogin' => $showLogin ?? false])
</body>
</html>
