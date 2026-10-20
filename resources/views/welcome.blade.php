<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth motion-reduce:scroll-auto">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Senior citizen services, program information, and office assistance from OSCA Santa Maria, Bulacan.">
    <title>eLINGAP | OSCA Santa Maria</title>
    <x-favicon />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="landing-page bg-white font-sans text-slate-600 antialiased">
    <a href="#landing-main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-3 focus:text-osca-primary">Skip to main content</a>

    <div class="bg-osca-primary-dark text-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2.5 text-xs sm:px-6 lg:px-8">
            <p>Municipality of Santa Maria, Bulacan</p>
            <span class="hidden items-center gap-2 text-blue-100 sm:inline-flex"><span class="size-1.5 rounded-full bg-amber-300" aria-hidden="true"></span>Office of the Senior Citizens Affairs</span>
        </div>
    </div>

    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex min-h-20 max-w-7xl items-center justify-between gap-2 px-4 sm:gap-6 sm:px-6 lg:px-8">
            <a href="#hero" data-view="home" class="flex shrink-0 items-center gap-2 rounded-lg sm:gap-3" aria-label="eLINGAP home">
                <img src="{{ asset('images/eLINGAP.png') }}" alt="" class="size-9 object-contain sm:size-12" width="48" height="48">
                <div><span class="block text-lg font-bold tracking-tight text-osca-primary-dark sm:text-xl">eLINGAP</span><span class="block text-[10px] text-slate-500 sm:text-xs">OSCA Santa Maria</span></div>
            </a>
            <nav class="ml-auto hidden items-center gap-7 text-sm font-medium lg:flex" aria-label="Main menu">
                <a href="#hero" data-view="home" data-view-link class="py-3 transition-colors hover:text-osca-primary aria-[current=page]:text-osca-primary">Home</a>
                <a href="#services" data-home-section class="py-3 transition-colors hover:text-osca-primary">Services</a>
                <a href="#programs" data-home-section class="py-3 transition-colors hover:text-osca-primary">Programs</a>
                <a href="#about-view" data-view="about" data-view-link class="py-3 transition-colors hover:text-osca-primary aria-[current=page]:text-osca-primary">About</a>
                <a href="#office-contact" class="py-3 transition-colors hover:text-osca-primary">Contact</a>
            </nav>
            <div class="flex items-center gap-2 sm:gap-4">
                <button type="button" data-open-modal="login" aria-haspopup="dialog" aria-controls="login-modal" class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg bg-osca-primary px-3 py-2.5 text-xs font-semibold text-white transition-colors hover:bg-osca-primary-dark sm:px-5 sm:text-sm">Staff sign in <span class="hidden sm:inline" aria-hidden="true">&rarr;</span></button>
                <button type="button" id="mobile-menu-btn" class="inline-flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-slate-200 text-osca-primary-dark hover:bg-slate-50 lg:hidden" aria-label="Toggle menu" aria-controls="mobile-menu" aria-expanded="false">
                    <svg id="menu-icon-bars" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg id="menu-icon-close" class="hidden size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M6 18 18 6"/></svg>
                </button>
            </div>
        </div>
        <nav id="mobile-menu" class="hidden max-h-[calc(100dvh-8rem)] overflow-y-auto border-t border-slate-100 bg-white px-4 py-3 lg:hidden" aria-label="Mobile menu">
            @foreach (['hero' => 'Home', 'services' => 'Services', 'programs' => 'Programs', 'about-view' => 'About', 'office-contact' => 'Contact'] as $target => $label)
                <a href="#{{ $target }}" @if ($target === 'hero' || $target === 'about-view') data-view="{{ $target === 'hero' ? 'home' : 'about' }}" data-view-link @elseif ($target !== 'office-contact') data-home-section @endif class="block rounded-lg px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50 aria-[current=page]:bg-blue-50 aria-[current=page]:text-osca-primary">{{ $label }}</a>
            @endforeach
        </nav>
    </header>

    <main id="landing-main" tabindex="-1">
        <div id="landing-view">
            <section id="hero" class="overflow-hidden border-b border-slate-200 bg-[#f4f7fb]" aria-labelledby="hero-title">
                <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-12 lg:gap-16 lg:px-8 lg:py-20">
                    <div class="flex flex-col items-start gap-6 lg:col-span-7">
                        <p class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white px-3.5 py-2 text-xs font-semibold text-osca-primary"><span class="size-1.5 rounded-full bg-osca-primary" aria-hidden="true"></span>Senior citizen services, brought closer</p>
                        <h1 id="hero-title" class="max-w-2xl text-4xl leading-[1.12] font-semibold tracking-tight text-slate-900 sm:text-5xl lg:text-[3.5rem]">Better care.<br>Stronger connections.<br><span class="text-osca-primary">For our seniors.</span></h1>
                        <p class="max-w-xl text-base leading-relaxed text-slate-600">Connecting Santa Maria's senior citizens with the services they need. Explore welfare programs, learn about registration, and stay informed through OSCA updates.</p>
                        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                            <a href="#services" data-home-section class="inline-flex min-h-12 items-center justify-center gap-3 rounded-lg bg-osca-primary px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-osca-primary-dark">Explore our services <span aria-hidden="true">&rarr;</span></a>
                            <a href="#office-contact" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition-colors hover:border-osca-primary hover:text-osca-primary">Contact the office</a>
                        </div>
                        <p class="flex items-center gap-2 text-xs text-slate-500"><svg class="size-4 shrink-0 text-osca-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m12 3 8 4v5c0 5-8 9-8 9s-8-4-8-9V7l8-4Z"/><path d="m8 12 3 3 5-6"/></svg>A dedicated platform for OSCA Santa Maria, Bulacan</p>
                    </div>
                    <aside class="relative lg:col-span-5" aria-label="About your OSCA office">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg shadow-blue-950/5">
                            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 text-xs font-medium text-slate-500"><span>Serving our senior community</span><img src="{{ asset('images/lgu-seal.png') }}" alt="Municipality of Santa Maria seal" class="size-9 object-contain" width="36" height="36"></div>
                            <div class="flex flex-col items-center gap-4 px-6 py-8 text-center sm:px-8">
                                <div class="flex size-40 items-center justify-center rounded-full bg-blue-50/70"><img src="{{ asset('images/eLINGAP.png') }}" alt="eLINGAP" class="size-32 object-contain" width="128" height="128"></div>
                                <div><p class="text-xl font-semibold tracking-tight text-osca-primary-dark">Care that keeps us connected.</p><p class="mt-2 text-sm leading-relaxed">Office of the Senior Citizens Affairs<br>Santa Maria, Bulacan</p></div>
                            </div>
                            <div class="grid grid-cols-3 divide-x divide-slate-200 border-t border-slate-200 bg-slate-50 px-3 py-5 text-center text-xs font-medium text-slate-700"><span>Senior records</span><span>Welfare benefits</span><span>SMS updates</span></div>
                        </div>
                    </aside>
                </div>
            </section>

            <section id="services" class="py-14 sm:py-20" aria-labelledby="services-title">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-8 flex flex-col gap-3 sm:mb-10 lg:flex-row lg:items-end lg:justify-between lg:gap-12">
                        <div><p class="text-xs font-semibold tracking-[0.15em] text-osca-primary uppercase">How we can help</p><h2 id="services-title" class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">One place. Essential services.</h2></div>
                        <p class="max-w-md text-sm leading-relaxed">Clear information and coordinated support for seniors, families, and the staff who serve them.</p>
                    </div>
                    <div class="grid gap-5 md:grid-cols-3">
                        @foreach ([['01', 'Senior citizen records', 'Learn about registration, supporting documents, and keeping your OSCA record up to date.', 'records', 'Registration guidance', 'users'], ['02', 'Benefits & programs', 'Explore active welfare programs and ask the office about requirements and eligibility.', 'programs', 'View current programs', 'heart-pulse'], ['03', 'SMS notifications', 'Receive office announcements and confirmed payout details through your registered mobile number.', 'sms', 'Learn about SMS updates', 'message-square']] as [$number, $title, $description, $target, $action, $icon])
                            <a href="#{{ $target }}" data-home-section class="group flex flex-col gap-5 rounded-xl border border-slate-200 bg-white p-6 transition-colors hover:border-blue-300 hover:bg-blue-50/30 sm:p-7">
                                <div class="flex items-center justify-between"><span class="inline-flex size-12 items-center justify-center rounded-xl bg-blue-50 text-osca-primary" aria-hidden="true"><x-admin.icon :name="$icon" :size="23" /></span><span class="text-xs font-medium text-slate-400">{{ $number }}</span></div>
                                <div><h3 class="text-lg font-semibold text-slate-900">{{ $title }}</h3><p class="mt-2 text-sm leading-relaxed">{{ $description }}</p></div>
                                <span class="mt-auto flex items-center justify-between gap-3 border-t border-slate-100 pt-4 text-sm font-semibold text-osca-primary">{{ $action }}<span aria-hidden="true">&rarr;</span></span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>

            @include('public.programs')

            <section id="records" class="border-y border-slate-200 py-14 sm:py-20" aria-labelledby="records-title">
                <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-12 lg:gap-16 lg:px-8">
                    <div class="lg:col-span-5">
                        <p class="text-xs font-semibold tracking-[0.15em] text-osca-primary uppercase">Registration & records</p>
                        <h2 id="records-title" class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">Your first step to<br>connected care.</h2>
                        <p class="mt-5 text-base leading-relaxed">Visit OSCA for registration assistance. Our staff will help review your information and explain the next steps for your senior citizen record.</p>
                        <a href="#office-contact" class="mt-6 inline-flex min-h-11 items-center gap-3 rounded text-sm font-semibold text-osca-primary hover:underline">Ask about registration <span aria-hidden="true">&rarr;</span></a>
                    </div>
                    <ol class="flex flex-col gap-0 lg:col-span-7">
                        @foreach ([['Visit the OSCA office', 'Ask staff about the current requirements for registration or updating an existing record.'], ['Have your documents reviewed', 'Staff verify your details and supporting documents. A representative may need additional authorization.'], ['Keep your details up to date', 'Tell the office about changes to your address or mobile number so your records and updates stay accurate.']] as [$title, $description])
                            <li class="flex gap-5 border-b border-slate-200 py-6 first:pt-0 last:border-0 last:pb-0"><span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-sm font-semibold text-osca-primary">{{ $loop->iteration }}</span><div><h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3><p class="mt-2 text-sm leading-relaxed">{{ $description }}</p></div></li>
                        @endforeach
                    </ol>
                </div>
            </section>

            <section id="sms" class="py-14 sm:py-20" aria-labelledby="sms-title">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid overflow-hidden rounded-2xl bg-osca-primary-dark text-white lg:grid-cols-2">
                        <div class="flex flex-col items-start gap-5 p-7 sm:p-10 lg:p-12">
                            <p class="text-xs font-semibold tracking-[0.15em] text-blue-200 uppercase">Stay informed</p>
                            <h2 id="sms-title" class="text-3xl font-semibold tracking-tight sm:text-4xl">Important updates.<br>Closer to home.</h2>
                            <p class="max-w-lg text-sm leading-relaxed text-blue-100">OSCA can send announcements, payout schedules, and claim requirements to your registered mobile number or your recorded representative's number.</p>
                            <a href="#office-contact" class="inline-flex min-h-11 items-center gap-3 rounded-lg border border-white/30 px-5 py-3 text-sm font-semibold transition-colors hover:bg-white/10">Update your contact details <span aria-hidden="true">&rarr;</span></a>
                        </div>
                        <div class="flex items-center justify-center bg-white/5 p-7 sm:p-10 lg:p-12">
                            <div class="w-full max-w-md rounded-2xl border border-white/20 bg-white p-6 text-slate-600 shadow-lg">
                                <div class="flex items-center gap-3 border-b border-slate-100 pb-4"><span class="inline-flex size-11 items-center justify-center rounded-full bg-blue-50 text-osca-primary" aria-hidden="true"><x-admin.icon name="message-square" :size="22" /></span><div><p class="text-sm font-semibold text-slate-900">OSCA updates</p><p class="text-xs text-slate-500">What your notifications may include</p></div></div>
                                <ul class="mt-5 flex flex-col gap-4 text-sm">
                                    @foreach (['Program and benefit period', 'Confirmed schedule and location', 'Documents and claim requirements'] as $item)
                                        <li class="flex items-center gap-3"><span class="inline-flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-xs text-emerald-700" aria-hidden="true">&check;</span>{{ $item }}</li>
                                    @endforeach
                                </ul>
                                <p class="mt-5 border-t border-slate-100 pt-4 text-xs leading-relaxed text-slate-500">For your specific schedule or eligibility, please confirm with the OSCA office.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <x-about-elingap />
    </main>

    <footer id="office-contact" class="scroll-mt-24 border-t border-slate-200 bg-slate-50" aria-labelledby="contact-title">
        <div class="mx-auto max-w-7xl px-4 pt-12 pb-6 sm:px-6 sm:pt-16 lg:px-8">
            <div class="grid gap-10 pb-10 md:grid-cols-2 lg:grid-cols-12 lg:gap-16">
                <div class="flex flex-col items-start gap-5 lg:col-span-4">
                    <a href="#hero" data-view="home" class="flex items-center gap-3 rounded-lg"><img src="{{ asset('images/eLINGAP.png') }}" alt="" class="size-14 object-contain" width="56" height="56" loading="lazy"><div><p class="text-xl font-bold tracking-tight text-osca-primary-dark">eLINGAP</p><p class="text-xs text-slate-500">Care. Connection. Community.</p></div></a>
                    <p class="max-w-sm text-sm leading-relaxed">Supporting senior citizen services through organized records, coordinated benefits, and direct communication.</p>
                </div>
                <div class="lg:col-span-5">
                    <p class="text-xs font-semibold tracking-[0.15em] text-osca-primary uppercase">We're here to help</p>
                    <h2 id="contact-title" class="mt-3 text-xl font-semibold tracking-tight text-slate-900">{{ $publicOffice['office_name'] ?? 'Office of the Senior Citizens Affairs' }}</h2>
                    @if (!empty($publicOffice['organization_name']))<p class="mt-1 text-sm">{{ $publicOffice['organization_name'] }}</p>@endif
                    <dl class="mt-5 flex flex-col gap-4 text-sm">
                        <div><dt class="font-medium text-slate-800">Visit us</dt><dd class="mt-1 leading-relaxed">{{ $publicOffice['office_address'] ?? 'Contact OSCA Santa Maria, Bulacan for directions to the office.' }}</dd></div>
                        @if (!empty($publicOffice['support_email']))<div><dt class="font-medium text-slate-800">Email</dt><dd class="mt-1 break-words">{{ $publicOffice['support_email'] }}</dd></div>@endif
                        @if (!empty($publicOffice['support_phone']))<div><dt class="font-medium text-slate-800">Phone</dt><dd class="mt-1 break-words">{{ $publicOffice['support_phone'] }}</dd></div>@endif
                        <div><dt class="font-medium text-slate-800">Office hours</dt><dd class="mt-1 leading-relaxed">{{ $publicOffice['office_hours'] ?? 'Please confirm current office hours before your visit.' }}</dd></div>
                    </dl>
                </div>
                <nav class="flex flex-col items-start gap-3 text-sm lg:col-span-3" aria-label="Footer navigation">
                    <h3 class="mb-1 font-semibold text-slate-900">Quick links</h3>
                    <a href="#services" data-home-section class="rounded py-1 hover:text-osca-primary">Our services</a>
                    <a href="#programs" data-home-section class="rounded py-1 hover:text-osca-primary">Active programs</a>
                    <a href="#records" data-home-section class="rounded py-1 hover:text-osca-primary">Registration guidance</a>
                    <a href="#about-view" data-view="about" class="rounded py-1 hover:text-osca-primary">About eLINGAP</a>
                    <button type="button" data-open-modal="login" aria-haspopup="dialog" aria-controls="login-modal" class="cursor-pointer rounded py-1 font-medium text-osca-primary hover:underline">Staff sign in <span aria-hidden="true">&rarr;</span></button>
                </nav>
            </div>
            <div class="flex flex-col gap-4 border-t border-slate-200 pt-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between"><p>&copy; {{ date('Y') }} OSCA Santa Maria, Bulacan. All rights reserved.</p><button type="button" data-back-to-top class="inline-flex min-h-11 cursor-pointer items-center gap-2 self-start rounded-lg px-3 text-sm font-medium text-slate-600 hover:bg-slate-100 sm:self-auto">Back to top <span aria-hidden="true">&uarr;</span></button></div>
        </div>
    </footer>

    @include('auth._login-dialog', ['showLogin' => $showLogin ?? false])
</body>
</html>
