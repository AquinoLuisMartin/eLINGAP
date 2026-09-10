<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>eLINGAP — Office of the Senior Citizens Affairs | Santa Maria, Bulacan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-osca-body antialiased">

    {{-- Utility bar --}}
    <div class="bg-osca-primary text-white/90 text-[10px] sm:text-xs border-b border-white/15">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-2 flex flex-wrap sm:flex-nowrap items-center justify-between gap-1.5 sm:gap-4">
            <span class="text-white/60 whitespace-nowrap hidden sm:inline">{{ now()->format('l, F j, Y') }}</span>
            <div class="w-full sm:flex-1 flex items-center justify-center sm:justify-start gap-4 overflow-hidden min-w-0">
                <a href="#announcements" class="w-full sm:w-auto truncate text-center sm:text-left hover:text-white transition-colors">3rd Quarter Social Pension payout begins October 12</a>
                <span class="hidden lg:inline text-white/30">|</span>
                <a href="#announcements" class="hidden lg:inline truncate hover:text-white transition-colors">Free Cataract Screening &amp; Senior Eye Care — Oct 20</a>
            </div>
            <a href="mailto:osca@santamariabulacan.gov.ph" class="hidden md:inline hover:text-white transition-colors whitespace-nowrap">osca@santamariabulacan.gov.ph</a>
        </div>
    </div>

    {{-- Primary navigation --}}
    <header class="bg-white border-b border-osca-border sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-18">
                {{-- Logo --}}
                <a href="#hero" class="flex items-center gap-2.5 shrink-0">
                    <img src="{{ asset('images/eLINGAP.png') }}" alt="eLINGAP logo" class="size-12 sm:size-14 object-contain" width="56" height="56">
                    <div>
                        <span class="text-xl sm:text-2xl font-bold tracking-tight text-osca-ink leading-none block">eLINGAP</span>
                        <span class="text-[10px] text-osca-body hidden xs:block font-medium">Santa Maria OSCA</span>
                    </div>
                </a>

                {{-- Desktop menu --}}
                <nav class="hidden xl:flex items-center gap-4 2xl:gap-6 text-sm font-medium text-osca-body" aria-label="Main menu">
                    <a href="#hero" class="text-osca-primary font-semibold">Home</a>
                    <a href="#programs" class="hover:text-osca-primary transition-colors">Programs &amp; Benefits</a>
                    <a href="#records" class="hover:text-osca-primary transition-colors">Records</a>
                    <a href="#sms" class="hover:text-osca-primary transition-colors">SMS Notifications</a>
                    <a href="#about" class="hover:text-osca-primary transition-colors">About OSCA</a>
                    <a href="#emergency" class="hover:text-osca-primary transition-colors">Contact</a>
                </nav>

                {{-- Right controls --}}
                <div class="flex items-center gap-2">
                    <button type="button" data-open-modal="search" class="size-11 sm:size-9 min-h-[44px] min-w-[44px] sm:min-h-0 sm:min-w-0 inline-flex items-center justify-center rounded-lg sm:rounded-md text-osca-body hover:text-osca-primary hover:bg-osca-muted transition-colors" aria-label="Search site">
                        <svg class="size-5 sm:size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </button>
                    <button type="button" id="mobile-menu-btn" class="size-11 min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg text-osca-ink xl:hidden hover:bg-osca-muted transition-colors" aria-label="Toggle menu" aria-expanded="false">
                        <svg id="menu-icon-bars" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                        <svg id="menu-icon-close" class="size-6 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile drawer --}}
        <nav id="mobile-menu" class="hidden xl:hidden border-t border-osca-border bg-white px-4 pt-3 pb-6 space-y-1.5 max-h-[calc(100vh-4.5rem)] overflow-y-auto" aria-label="Mobile menu">
            <a href="#hero" class="block rounded-lg px-3.5 py-3 text-base font-semibold text-osca-primary bg-osca-muted">Home</a>
            <a href="#programs" class="block rounded-lg px-3.5 py-3 text-base font-medium text-osca-ink hover:bg-osca-muted">Programs &amp; Benefits</a>
            <a href="#records" class="block rounded-lg px-3.5 py-3 text-base font-medium text-osca-ink hover:bg-osca-muted">Records Management</a>
            <a href="#sms" class="block rounded-lg px-3.5 py-3 text-base font-medium text-osca-ink hover:bg-osca-muted">SMS Notifications</a>
            <a href="#about" class="block rounded-lg px-3.5 py-3 text-base font-medium text-osca-ink hover:bg-osca-muted">About OSCA</a>
            <a href="#emergency" class="block rounded-lg px-3.5 py-3 text-base font-medium text-osca-ink hover:bg-osca-muted">Contact &amp; Hotlines</a>
        </nav>
    </header>

    <main>
        {{-- Light hero band --}}
        <section id="hero" class="relative overflow-hidden pt-10 pb-20 sm:pt-14 sm:pb-24 lg:pt-16 lg:pb-28">
            {{-- Decorative organic shapes --}}
            <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
                <div class="absolute -right-20 top-10 size-80 rounded-full border border-osca-primary/10"></div>
                <div class="absolute right-36 top-28 size-48 rounded-full bg-osca-primary/5"></div>
                <div class="absolute left-10 bottom-16 size-32 rounded-full bg-osca-primary/5"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-center">
                    {{-- Copy column --}}
                    <div class="lg:col-span-7 space-y-5 sm:space-y-6">
                        <span class="inline-block text-xs font-semibold uppercase tracking-widest text-osca-primary bg-osca-primary/10 px-3 py-1 rounded-full border border-osca-primary/15">Welcome to eLINGAP</span>
                        <h1 class="text-3xl sm:text-4xl lg:text-[46px] xl:text-[50px] font-bold text-osca-ink tracking-tight leading-[1.15] sm:leading-[1.1]">
                            Caring for Santa Maria's <span class="text-osca-primary underline decoration-osca-primary/30 decoration-2">Senior Citizens</span>
                        </h1>
                        <p class="text-sm sm:text-base lg:text-lg leading-relaxed max-w-2xl">
                            An integrated web-based records management and automated SMS notification system for the Office of the Senior Citizens Affairs of Santa Maria, Bulacan.
                        </p>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-2">
                            <a href="#records" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-6 sm:px-7 py-3.5 text-base sm:text-sm font-semibold text-osca-primary hover:bg-osca-muted transition-colors shadow-md min-h-[44px]">
                                Register Now
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                            <a href="#programs" class="inline-flex items-center justify-center rounded-lg border-2 border-osca-primary/70 px-6 sm:px-7 py-3 text-base sm:text-sm font-semibold text-osca-primary hover:bg-osca-primary/10 transition-colors min-h-[44px]">
                                Check Benefit Status
                            </a>
                        </div>
                        <div class="pt-2 flex items-center gap-4 text-xs text-osca-body">
                            <div class="flex items-center gap-1.5">
                                <span class="size-2 rounded-full bg-osca-primary"></span>
                                <span class="size-2 rounded-full bg-osca-primary/40"></span>
                                <span class="size-2 rounded-full bg-osca-primary/40"></span>
                            </div>
                            <span>01 / 03 &middot; Public Senior Welfare &amp; Records Digitization</span>
                        </div>
                    </div>

                    {{-- Right visual card --}}
                    <div class="lg:col-span-5 relative flex justify-center lg:justify-end">
                        <div class="relative w-full max-w-md">
                            <div class="rounded-xl bg-osca-primary border border-osca-primary-dark/25 p-5 sm:p-7 shadow-2xl text-white">
                                <div class="flex items-center gap-3.5 sm:gap-4 border-b border-white/15 pb-4 sm:pb-5">
                                    <div class="size-12 sm:size-14 rounded-full bg-white/15 flex items-center justify-center text-white shrink-0">
                                        <svg class="size-7 sm:size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <span class="text-[11px] uppercase tracking-wider text-white/75 font-semibold block">OSCA Frontline Service</span>
                                        <h2 class="text-lg sm:text-xl font-bold text-white truncate">Santa Maria Senior Center</h2>
                                        <p class="text-xs text-white/75">Serving 24 Barangays of Bulacan</p>
                                    </div>
                                </div>
                                <div class="mt-4 sm:mt-5 space-y-2.5 sm:space-y-3 text-sm">
                                    <div class="rounded-lg bg-white/10 p-3 sm:p-3.5 flex items-start gap-3 border border-white/10">
                                        <span class="size-7 rounded-full bg-osca-success text-white flex items-center justify-center shrink-0 mt-0.5">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-white text-xs sm:text-sm">Verified Senior Registry</p>
                                            <p class="text-xs text-white/75 mt-0.5">Automated validation of senior IDs, eligibility, and records.</p>
                                        </div>
                                    </div>
                                    <div class="rounded-lg bg-white/10 p-3 sm:p-3.5 flex items-start gap-3 border border-white/10">
                                        <span class="size-7 rounded-full bg-white/20 text-white flex items-center justify-center shrink-0 mt-0.5">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-white text-xs sm:text-sm">Direct SMS Broadcasts</p>
                                            <p class="text-xs text-white/75 mt-0.5">Instant alerts for pension schedules sent straight to seniors' phones.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-5 pt-3.5 border-t border-white/15 flex flex-wrap items-center justify-between gap-x-3 gap-y-1 text-xs text-white/70">
                                    <span class="min-w-0">Republic Act No. 9994 Compliance</span>
                                    <span class="font-semibold text-white">Active Portal</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 3-Up Feature Cards (Overlapping Hero) --}}
        <section class="relative -mt-14 sm:-mt-20 z-20 pb-12 sm:pb-16" aria-label="Core Services">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6 lg:gap-8">
                    {{-- Card 1: Benefits & Programs --}}
                    <article class="bg-white rounded-xl border border-osca-border border-t-4 border-t-osca-primary p-6 sm:p-7 shadow-lg flex flex-col justify-between">
                        <div>
                            <div class="size-12 rounded-lg bg-osca-primary/10 text-osca-primary flex items-center justify-center mb-4">
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"/><path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-osca-ink">Benefits &amp; Programs</h3>
                            <p class="mt-2 text-sm text-osca-body leading-relaxed">Track quarterly social pension distributions, local merchant discounts, medical support subsidies, and funeral grants.</p>
                        </div>
                        <div class="mt-5 pt-4 border-t border-osca-border">
                            <a href="#programs" class="inline-flex items-center gap-1.5 text-sm font-semibold text-osca-primary hover:underline py-1">
                                Read More <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                        </div>
                    </article>

                    {{-- Card 2: Records Management --}}
                    <article class="bg-white rounded-xl border border-osca-border border-t-4 border-t-osca-primary p-6 sm:p-7 shadow-lg flex flex-col justify-between">
                        <div>
                            <div class="size-12 rounded-lg bg-osca-primary/10 text-osca-primary flex items-center justify-center mb-4">
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-osca-ink">Records Management</h3>
                            <p class="mt-2 text-sm text-osca-body leading-relaxed">Unified digital senior registry across all 24 barangays for instant ID validation, status updates, and caregiver links.</p>
                        </div>
                        <div class="mt-5 pt-4 border-t border-osca-border">
                            <a href="#records" class="inline-flex items-center gap-1.5 text-sm font-semibold text-osca-primary hover:underline py-1">
                                Read More <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                        </div>
                    </article>

                    {{-- Card 3: SMS Notifications --}}
                    <article class="bg-white rounded-xl border border-osca-border border-t-4 border-t-osca-primary p-6 sm:p-7 shadow-lg flex flex-col justify-between">
                        <div>
                            <div class="size-12 rounded-lg bg-osca-primary/10 text-osca-primary flex items-center justify-center mb-4">
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 10h.01"/><path d="M12 10h.01"/><path d="M16 10h.01"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-osca-ink">SMS Notifications</h3>
                            <p class="mt-2 text-sm text-osca-body leading-relaxed">Automated, reliable text alerts informing seniors and families of payout dates, barangay assemblies, and health missions.</p>
                        </div>
                        <div class="mt-5 pt-4 border-t border-osca-border">
                            <a href="#sms" class="inline-flex items-center gap-1.5 text-sm font-semibold text-osca-primary hover:underline py-1">
                                Read More <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        {{-- Programs and Benefits Section --}}
        <section id="programs" class="scroll-mt-20 py-14 sm:py-20 bg-osca-muted/60 border-t border-osca-border" aria-label="Programs and Benefits">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mb-10 sm:mb-12">
                    <span class="text-xs font-semibold uppercase tracking-wider text-osca-primary">Republic Act No. 9994 &amp; Municipal Support</span>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-osca-ink tracking-tight mt-1">
                        Programs &amp; Welfare <span class="text-osca-primary">Benefits</span>
                    </h2>
                    <div class="w-16 h-1 bg-osca-primary rounded-full mt-3" aria-hidden="true"></div>
                    <p class="mt-3 text-sm sm:text-base text-osca-body leading-relaxed">
                        Santa Maria senior citizens are entitled to financial aid, milestone cash gifts, healthcare support, and statutory discounts.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 sm:gap-6">
                    {{-- Benefit 1: Social Pension --}}
                    <article class="bg-white rounded-xl border border-osca-border p-5 sm:p-6 shadow-xs flex flex-col justify-between hover:border-osca-primary transition-colors">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-osca-primary/10 text-osca-primary">Quarterly Stipend</span>
                                <span class="text-xs text-osca-body font-medium">₱1,000/mo</span>
                            </div>
                            <h3 class="text-base sm:text-lg font-bold text-osca-ink">Social Pension for Indigent Seniors</h3>
                            <p class="mt-2 text-xs sm:text-sm text-osca-body leading-relaxed">
                                Direct financial assistance of ₱3,000 disbursed quarterly to qualified indigent seniors across all 24 barangays.
                            </p>
                        </div>
                        <div class="mt-5 pt-3 border-t border-osca-border flex items-center justify-between text-xs">
                            <span class="text-osca-body">Eligibility:</span>
                            <span class="font-semibold text-osca-success">60+ Indigent</span>
                        </div>
                    </article>

                    {{-- Benefit 2: Milestone Grants --}}
                    <article class="bg-white rounded-xl border border-osca-border p-5 sm:p-6 shadow-xs flex flex-col justify-between hover:border-osca-primary transition-colors">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-osca-warning/20 text-osca-ink">Milestone Grant</span>
                                <span class="text-xs text-osca-body font-medium">RA 10868</span>
                            </div>
                            <h3 class="text-base sm:text-lg font-bold text-osca-ink">Centenarian &amp; Birthday Cash Gifts</h3>
                            <p class="mt-2 text-xs sm:text-sm text-osca-body leading-relaxed">
                                ₱100,000 cash gift for seniors reaching 100 years, plus local honorarium for milestone birthdays (80, 85, 90, 95).
                            </p>
                        </div>
                        <div class="mt-5 pt-3 border-t border-osca-border flex items-center justify-between text-xs">
                            <span class="text-osca-body">Coverage:</span>
                            <span class="font-semibold text-osca-primary">80+ Residents</span>
                        </div>
                    </article>

                    {{-- Benefit 3: Healthcare --}}
                    <article class="bg-white rounded-xl border border-osca-border p-5 sm:p-6 shadow-xs flex flex-col justify-between hover:border-osca-primary transition-colors">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-osca-success/15 text-osca-success">Healthcare</span>
                                <span class="text-xs text-osca-body font-medium">RHU Clinic</span>
                            </div>
                            <h3 class="text-base sm:text-lg font-bold text-osca-ink">Free Maintenance &amp; Eye Care</h3>
                            <p class="mt-2 text-xs sm:text-sm text-osca-body leading-relaxed">
                                Monthly maintenance medicine refills at RHU stations, plus periodic free cataract screening missions.
                            </p>
                        </div>
                        <div class="mt-5 pt-3 border-t border-osca-border flex items-center justify-between text-xs">
                            <span class="text-osca-body">Location:</span>
                            <span class="font-semibold text-osca-ink">Municipal RHU</span>
                        </div>
                    </article>

                    {{-- Benefit 4: Discounts --}}
                    <article class="bg-white rounded-xl border border-osca-border p-5 sm:p-6 shadow-xs flex flex-col justify-between hover:border-osca-primary transition-colors">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-osca-primary/10 text-osca-primary">Statutory</span>
                                <span class="text-xs text-osca-body font-medium">Nationwide</span>
                            </div>
                            <h3 class="text-base sm:text-lg font-bold text-osca-ink">20% Discount &amp; VAT Exemption</h3>
                            <p class="mt-2 text-xs sm:text-sm text-osca-body leading-relaxed">
                                Mandatory 20% discount and VAT exemption on medicines, hospital services, groceries, dining, and transit.
                            </p>
                        </div>
                        <div class="mt-5 pt-3 border-t border-osca-border flex items-center justify-between text-xs">
                            <span class="text-osca-body">Requirement:</span>
                            <span class="font-semibold text-osca-primary">Valid OSCA ID</span>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        {{-- Records Management Section --}}
        <section id="records" class="scroll-mt-20 py-14 sm:py-20 bg-white border-t border-osca-border" aria-label="Records Management">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-center">
                    <div class="lg:col-span-7 space-y-5">
                        <span class="text-xs font-semibold uppercase tracking-wider text-osca-primary">Barangay-Synchronized Registry</span>
                        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-osca-ink tracking-tight">
                            Digital Senior Citizen <span class="text-osca-primary">Records Registry</span>
                        </h2>
                        <div class="w-16 h-1 bg-osca-primary rounded-full mt-3" aria-hidden="true"></div>
                        <p class="text-sm sm:text-base text-osca-body leading-relaxed">
                            eLINGAP provides unified records verification across Santa Maria's 24 barangays, accelerating ID renewals, benefit validation, and family caregiver contacts.
                        </p>
                        <div class="space-y-3 pt-2">
                            <div class="flex items-start gap-3 p-3.5 rounded-xl bg-osca-muted border border-osca-border">
                                <span class="size-6 rounded-full bg-osca-success text-white flex items-center justify-center shrink-0 mt-0.5 text-xs font-bold">&check;</span>
                                <div>
                                    <h4 class="text-xs sm:text-sm font-bold text-osca-ink">Unified 24-Barangay Masterlist</h4>
                                    <p class="text-xs text-osca-body mt-0.5">Centralized database syncs senior records from Poblacion to Tumana in real-time.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3.5 rounded-xl bg-osca-muted border border-osca-border">
                                <span class="size-6 rounded-full bg-osca-success text-white flex items-center justify-center shrink-0 mt-0.5 text-xs font-bold">&check;</span>
                                <div>
                                    <h4 class="text-xs sm:text-sm font-bold text-osca-ink">Caregiver &amp; Emergency Contact Link</h4>
                                    <p class="text-xs text-osca-body mt-0.5">Automated linking of next-of-kin mobile numbers ensures families stay notified.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3.5 rounded-xl bg-osca-muted border border-osca-border">
                                <span class="size-6 rounded-full bg-osca-success text-white flex items-center justify-center shrink-0 mt-0.5 text-xs font-bold">&check;</span>
                                <div>
                                    <h4 class="text-xs sm:text-sm font-bold text-osca-ink">Instant ID &amp; Pension Validation</h4>
                                    <p class="text-xs text-osca-body mt-0.5">OSCA counter staff verify eligibility within seconds with zero paper bottlenecks.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-5">
                        <div class="rounded-2xl border-2 border-osca-border bg-osca-muted p-6 sm:p-7 shadow-lg">
                            <div class="flex items-center justify-between border-b border-osca-border pb-4">
                                <div>
                                    <span class="text-xs uppercase tracking-wider text-osca-primary font-bold">Frontline Verification</span>
                                    <h3 class="text-lg font-bold text-osca-ink">Senior ID Counter</h3>
                                </div>
                                <span class="px-2.5 py-1 rounded-full bg-osca-success/15 text-osca-success text-xs font-bold">Express Windows</span>
                            </div>
                            <div class="mt-5 space-y-3 text-xs">
                                <div class="bg-white p-3.5 rounded-xl border border-osca-border flex flex-col items-start gap-1 sm:flex-row sm:items-center sm:justify-between">
                                    <span class="text-osca-body">Processing Window:</span>
                                    <span class="font-bold text-osca-ink">Window 1 &amp; Window 2</span>
                                </div>
                                <div class="bg-white p-3.5 rounded-xl border border-osca-border flex flex-col items-start gap-1 sm:flex-row sm:items-center sm:justify-between">
                                    <span class="text-osca-body">Required Documents:</span>
                                    <span class="font-bold text-osca-ink">Barangay Cert &amp; Valid ID</span>
                                </div>
                                <div class="bg-white p-3.5 rounded-xl border border-osca-border flex flex-col items-start gap-1 sm:flex-row sm:items-center sm:justify-between">
                                    <span class="text-osca-body">Average Release Time:</span>
                                    <span class="font-bold text-osca-success">Same-Day Processing</span>
                                </div>
                            </div>
                            <div class="mt-6 pt-4 border-t border-osca-border">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- SMS Notifications Section --}}
        <section id="sms" class="scroll-mt-20 py-14 sm:py-20 bg-linear-to-b from-osca-primary to-osca-primary-dark text-white" aria-label="SMS Notifications">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-center">
                    <div class="lg:col-span-7 space-y-5">
                        <span class="inline-block text-xs font-semibold uppercase tracking-wider text-white/80 bg-white/10 px-3 py-1 rounded-full border border-white/15">Automated Mobile Alert Dispatcher</span>
                        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-white">
                            Direct SMS Alerts Sent Straight to <span class="text-amber-400">Seniors &amp; Families</span>
                        </h2>
                        <div class="w-16 h-1 bg-amber-400 rounded-full mt-3" aria-hidden="true"></div>
                        <p class="text-sm sm:text-base text-white/85 leading-relaxed">
                            Never miss an assembly, medicine distribution, or pension release date. eLINGAP sends automatic SMS reminders directly to registered mobile phones.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 pt-2">
                            <div class="bg-white/10 border border-white/15 rounded-xl p-4 text-center backdrop-blur-xs">
                                <p class="text-2xl sm:text-3xl font-bold text-amber-400">18,400+</p>
                                <p class="text-xs text-white/80 mt-1">Seniors Reached</p>
                            </div>
                            <div class="bg-white/10 border border-white/15 rounded-xl p-4 text-center backdrop-blur-xs">
                                <p class="text-2xl sm:text-3xl font-bold text-emerald-400">98.6%</p>
                                <p class="text-xs text-white/80 mt-1">Delivery Success</p>
                            </div>
                            <div class="bg-white/10 border border-white/15 rounded-xl p-4 text-center backdrop-blur-xs">
                                <p class="text-2xl sm:text-3xl font-bold text-white">100% Free</p>
                                <p class="text-xs text-white/80 mt-1">Free Public Service</p>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-5 flex justify-center">
                        <div class="w-full max-w-sm bg-osca-ink border-4 border-white/15 rounded-3xl p-5 shadow-2xl">
                            <div class="flex items-center justify-between border-b border-white/10 pb-2.5 text-xs text-white/60">
                                <span class="font-bold text-white">OSCA-SANTA-MARIA</span>
                                <span>Official SMS</span>
                            </div>
                            <div class="mt-4 space-y-3 text-xs">
                                <div class="bg-white/15 rounded-2xl rounded-tl-none p-4 text-white leading-relaxed border border-white/10">
                                    <p class="font-bold text-amber-300 text-[11px] uppercase tracking-wide mb-1">Paunawa sa Social Pension:</p>
                                    <p>Magandang araw po! Ang 3rd Quarter Social Pension payout (P3,000) ay gaganapin sa Oct 12 sa Santa Maria Municipal Gym mula 8:00 AM.</p>
                                    <p class="text-[10px] text-white/70 mt-2">Mangyaring dalhin ang inyong OSCA ID at booklet.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Welcome to eLINGAP --}}
        <section id="about" class="scroll-mt-20 py-14 sm:py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-center">
                    <div class="lg:col-span-7 space-y-5">
                        <h2 class="text-3xl sm:text-4xl font-bold text-osca-ink tracking-tight">
                            Welcome to <span class="text-osca-primary">eLINGAP</span>
                        </h2>
                        <div class="w-16 h-1 bg-osca-primary rounded-full" aria-hidden="true"></div>
                        <p class="text-[15px] sm:text-base leading-relaxed">
                            eLINGAP is the official records management and automated SMS notification system of the Office of the Senior Citizens Affairs (OSCA) in the Municipality of Santa Maria, Bulacan. We modernize senior citizen welfare services by providing an accurate digital registry, paperless status tracking, and direct communication to every household.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                            @foreach (['Online Registration & Verification', 'Benefit Tracking & Distribution', 'Automated SMS Reminders', '24-Barangay Records Sync'] as $item)
                                <div class="flex items-start gap-3">
                                    <span class="size-6 rounded-full bg-osca-primary text-white flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                    </span>
                                    <span class="text-sm font-medium text-osca-ink">{{ $item }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="pt-5 border-t border-osca-border flex items-center gap-4">
                            <div class="size-12 rounded-full bg-osca-primary text-white flex items-center justify-center font-bold text-sm shrink-0">OSCA</div>
                            <div>
                                <p class="text-sm font-bold text-osca-ink">Office of the Senior Citizens Affairs</p>
                                <p class="text-xs">Municipal Hall Complex, Santa Maria, Bulacan</p>
                            </div>
                        </div>
                    </div>

                    {{-- Video card --}}
                    <div class="lg:col-span-5">
                        <div class="rounded-xl overflow-hidden shadow-lg border border-osca-border">
                            <div class="aspect-16/10 sm:aspect-4/3 lg:aspect-3/4 bg-osca-primary relative flex items-center justify-center">
                                <div class="absolute inset-0 opacity-20 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:20px_20px]"></div>
                                <div class="absolute inset-0 bg-linear-to-t from-osca-ink/60 via-transparent to-transparent"></div>
                                <button type="button" class="relative z-10 size-16 sm:size-20 rounded-full bg-white text-osca-primary flex items-center justify-center shadow-xl hover:scale-105 transition-transform" aria-label="Play presentation video">
                                    <svg class="size-7 sm:size-9 ml-1" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                </button>
                                <div class="absolute bottom-0 inset-x-0 p-4 sm:p-5 text-white z-10">
                                    <p class="font-bold text-base sm:text-lg">System Walkthrough &amp; Guide</p>
                                    <p class="text-xs text-white/80 mt-0.5">How eLINGAP serves Santa Maria's seniors</p>
                                </div>
                            </div>
                            <div class="bg-white border-t border-osca-border p-3.5 sm:p-4 flex flex-col items-start gap-1 sm:flex-row sm:items-center sm:justify-between text-xs">
                                <span class="font-medium text-osca-ink">Community Outreach &amp; Orientation</span>
                                <span class="text-osca-primary font-semibold">Duration: 2 mins</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Emergency & Hotline numbers --}}
        <section id="emergency" class="scroll-mt-20 py-14 sm:py-20 bg-osca-muted" aria-label="Emergency Hotlines">
            <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid w-full grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-14 items-start">
                    <div class="w-full min-w-0 lg:col-span-12 space-y-5 sm:space-y-6">
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-osca-primary">24/7 Municipal First Responders</span>
                            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-osca-ink tracking-tight mt-1">
                                OSCA Emergency &amp; <span class="text-osca-primary">Hotlines</span>
                            </h2>
                            <div class="w-16 h-1 bg-osca-primary rounded-full mt-3" aria-hidden="true"></div>
                            <p class="mt-3 text-sm sm:text-base text-osca-body leading-relaxed max-w-xl">
                                Direct contact lines for senior citizens and family caregivers during medical emergencies, urgent queries, or rescue operations.
                            </p>
                            <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6 text-sm">
                                <a href="tel:0449130248" class="font-bold text-osca-primary hover:underline">OSCA Desk: (044) 913-0248</a>
                                <span class="hidden sm:inline text-osca-border" aria-hidden="true">|</span>
                                <span class="text-osca-body">SMS Helpline: <strong class="text-osca-ink">+63 917 842 6722</strong></span>
                            </div>
                        </div>

                        {{-- Central hotline highlight --}}
                        <div class="rounded-xl bg-white border border-osca-border border-l-4 border-l-osca-primary p-4 sm:p-5 shadow-xs">
                            <div class="flex flex-col sm:flex-row items-start gap-4">
                                <span class="size-11 sm:size-12 rounded-lg bg-osca-primary/10 text-osca-primary flex items-center justify-center shrink-0">
                                    <svg class="size-5 sm:size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.88.37 1.85.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.96.33 1.93.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                </span>
                                <div class="flex-1 min-w-0">
                                    <span class="text-[11px] font-semibold text-osca-primary uppercase tracking-wider">Dedicated Frontline Desk</span>
                                    <h3 class="text-base sm:text-lg font-bold text-osca-ink mt-0.5">OSCA Santa Maria Central Hotline</h3>
                                    <p class="text-xs sm:text-sm text-osca-body mt-1">Monday to Friday, 8:00 AM – 5:00 PM for record updates, benefit inquiries, and home visitation requests.</p>
                                    <a href="tel:0449130248" class="inline-flex items-center justify-center gap-2 mt-3 w-full sm:w-auto rounded-lg bg-osca-primary px-5 py-2.5 sm:py-2 text-sm font-semibold text-white hover:bg-osca-primary-dark transition-colors shadow-xs min-h-[44px]">Call OSCA Desk Now</a>
                                </div>
                            </div>
                        </div>

                        {{-- Hotline directory --}}
                        @php
                            $hotlines = [
                                ['(044) 913-1191', 'MDRRMO Santa Maria', '24/7 emergency response, ambulance transport, and medical evacuation.', 'tel:0449131191', 'M10 10H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2ZM18 10h-4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2ZM12 2v4M8 6h8'],
                                ['(044) 913-2222', 'Santa Maria PNP Station', '24/7 public safety, law enforcement, and senior protection.', 'tel:0449132222', 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z'],
                                ['(044) 913-3333', 'Municipal Health (RHU)', 'Healthcare consultations, prescription verification, and senior clinics.', 'tel:0449133333', 'M22 12h-4l-3 9L9 3l-3 9H2'],
                                ['(044) 913-4444', 'BFP Santa Maria', 'Fire emergency, residential hazard inspections, and rescue services.', 'tel:0449134444', 'M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z'],
                            ];
                        @endphp
                        <div class="space-y-3">
                            @foreach ($hotlines as [$number, $name, $desc, $href, $icon])
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3.5 sm:gap-4 bg-white rounded-xl border border-osca-border p-4 shadow-xs">
                                    <div class="flex items-start sm:items-center gap-3.5 sm:gap-4 min-w-0">
                                        <span class="size-11 sm:size-12 rounded-lg bg-osca-primary/10 text-osca-primary flex items-center justify-center shrink-0">
                                            <svg class="size-5 sm:size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="{{ $icon }}"/></svg>
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <span class="text-lg sm:text-xl font-bold text-osca-primary block">{{ $number }}</span>
                                            <h4 class="text-xs sm:text-sm font-bold text-osca-ink">{{ $name }}</h4>
                                            <p class="text-xs text-osca-body mt-0.5">{{ $desc }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ $href }}" class="w-full sm:w-auto shrink-0 min-h-[44px] flex items-center justify-center rounded-lg bg-osca-primary text-white sm:bg-transparent sm:text-osca-primary sm:border sm:border-osca-primary px-5 py-2.5 sm:py-2 text-xs font-bold sm:font-semibold hover:bg-osca-primary hover:text-white transition-colors shadow-xs sm:shadow-none">Call {{ $name }}</a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Dark Leadership Band --}}
        <section class="bg-osca-ink text-white py-16 sm:py-24 relative overflow-hidden" aria-label="Leadership and Mandate">
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_30%_50%,white_0%,transparent_50%)]" aria-hidden="true"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-7 space-y-5">
                        <h2 class="text-3xl sm:text-4xl font-bold tracking-tight">Dedicated to the Dignity and Welfare of Our Elders</h2>
                        <div class="w-16 h-1 bg-osca-primary rounded-full" aria-hidden="true"></div>
                        <p class="text-[15px] sm:text-base text-white/85 leading-relaxed">
                            Under Republic Act No. 7432 and Republic Act No. 9994 (Expanded Senior Citizens Act), the Office of the Senior Citizens Affairs of Santa Maria works to advocate for the rights, privileges, and overall welfare of our elderly. eLINGAP modernizes this mandate through transparent records and automated outreach.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                            <div class="flex items-start gap-3">
                                <span class="size-6 rounded-full bg-white/20 text-white flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="text-sm font-medium text-white/90">Health &amp; Wellness Programs</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="size-6 rounded-full bg-white/20 text-white flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="text-sm font-medium text-white/90">Direct Benefit &amp; Pension Access</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="size-6 rounded-full bg-white/20 text-white flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="text-sm font-medium text-white/90">Community &amp; Barangay Engagement</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="size-6 rounded-full bg-white/20 text-white flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="text-sm font-medium text-white/90">Full Records Digitization</span>
                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-5">
                        <div class="rounded-xl border border-white/20 bg-white/5 p-7 sm:p-8 backdrop-blur-xs shadow-xl">
                            <div class="size-16 rounded-full bg-white/15 border border-white/25 flex items-center justify-center text-xl font-bold mb-5 text-white">OSCA</div>
                            <blockquote class="text-base sm:text-lg text-white/90 italic leading-relaxed border-l-4 border-osca-primary pl-4">
                                &ldquo;Our senior citizens are the pillars of Santa Maria's heritage. Ensuring their well-being, dignity, and prompt access to public services is our highest duty.&rdquo;
                            </blockquote>
                            <div class="mt-6 pt-4 border-t border-white/15">
                                <p class="font-bold text-base text-white">Office of the Senior Citizens Affairs</p>
                                <p class="text-xs text-white/70">Municipal Government of Santa Maria, Bulacan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Announcements --}}
        <section id="announcements" class="scroll-mt-20 py-14 sm:py-20 bg-osca-muted" aria-label="Announcements">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10">
                    <div class="lg:col-span-7 space-y-4">
                        <div class="mb-2">
                            <span class="text-xs font-semibold uppercase tracking-wider text-osca-primary">Community Updates</span>
                            <h3 class="text-2xl sm:text-3xl font-bold text-osca-ink mt-1">Upcoming Distribution Schedules</h3>
                            <div class="w-16 h-1 bg-osca-primary rounded-full mt-3" aria-hidden="true"></div>
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-xs sm:text-sm text-osca-body">Municipal Payouts &amp; Assemblies</span>
                                <span class="text-xs font-semibold text-osca-primary bg-osca-primary/10 px-3 py-1 rounded-full">2026 Calendar</span>
                            </div>
                        </div>

                        {{-- Schedule Card 1 --}}
                        <div class="rounded-xl border border-osca-border border-l-4 border-l-osca-success bg-white p-4 sm:p-5 flex flex-col sm:flex-row items-start gap-3.5 sm:gap-4 shadow-xs">
                            <div class="size-12 sm:size-14 rounded-lg bg-osca-primary/10 text-osca-primary flex flex-col items-center justify-center shrink-0 font-bold leading-tight">
                                <span class="text-[11px] uppercase">OCT</span>
                                <span class="text-base sm:text-lg leading-none">12</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded bg-osca-success/15 text-osca-success px-2 py-0.5 text-[11px] font-semibold">Social Pension</span>
                                    <span class="text-xs text-osca-body">8:00 AM – 3:00 PM</span>
                                </div>
                                <h4 class="text-sm sm:text-base font-semibold text-osca-ink mt-1">3rd Quarter Social Pension &amp; Cash Gift Payout</h4>
                                <p class="text-xs text-osca-body mt-1">Santa Maria Municipal Gymnasium for Barangays Poblacion, Bagbaguin, and Cay Pombo.</p>
                            </div>
                        </div>

                        {{-- Schedule Card 2 --}}
                        <div class="rounded-xl border border-osca-border border-l-4 border-l-osca-warning bg-white p-4 sm:p-5 flex flex-col sm:flex-row items-start gap-3.5 sm:gap-4 shadow-xs">
                            <div class="size-12 sm:size-14 rounded-lg bg-osca-primary/10 text-osca-primary flex flex-col items-center justify-center shrink-0 font-bold leading-tight">
                                <span class="text-[11px] uppercase">OCT</span>
                                <span class="text-base sm:text-lg leading-none">20</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded bg-osca-warning/20 text-osca-ink px-2 py-0.5 text-[11px] font-semibold">Medical Mission</span>
                                    <span class="text-xs text-osca-body">9:00 AM – 2:00 PM</span>
                                </div>
                                <h4 class="text-sm sm:text-base font-semibold text-osca-ink mt-1">Free Cataract Screening &amp; Senior Eye Care</h4>
                                <p class="text-xs text-osca-body mt-1">Municipal Health Office (RHU) in coordination with Bulacan Provincial Health.</p>
                            </div>
                        </div>

                        {{-- Schedule Card 3 --}}
                        <div class="rounded-xl border border-osca-border border-l-4 border-l-osca-primary bg-white p-4 sm:p-5 flex flex-col sm:flex-row items-start gap-3.5 sm:gap-4 shadow-xs">
                            <div class="size-12 sm:size-14 rounded-lg bg-osca-primary/10 text-osca-primary flex flex-col items-center justify-center shrink-0 font-bold leading-tight">
                                <span class="text-[11px] uppercase">OCT</span>
                                <span class="text-base sm:text-lg leading-none">28</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded bg-osca-primary/15 text-osca-primary px-2 py-0.5 text-[11px] font-semibold">Assembly</span>
                                    <span class="text-xs text-osca-body">1:30 PM – 4:00 PM</span>
                                </div>
                                <h4 class="text-sm sm:text-base font-semibold text-osca-ink mt-1">Quarterly Barangay Senior Citizen Chapter Assembly</h4>
                                <p class="text-xs text-osca-body mt-1">Updates on RA 9994 local compliance and welfare programs.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Right sidebar --}}
                    <div class="lg:col-span-5 space-y-5">
                        {{-- Weather --}}
                        <div class="rounded-xl border border-osca-border bg-white p-5 sm:p-6 shadow-xs">
                            <div class="flex items-center justify-between border-b border-osca-border pb-3.5">
                                <div>
                                    <span class="text-[11px] font-semibold uppercase tracking-wider text-osca-primary">Local Conditions</span>
                                    <h4 class="text-base font-bold text-osca-ink">Santa Maria, Bulacan</h4>
                                </div>
                                <span class="text-xs text-osca-body">Today</span>
                            </div>
                            <div class="mt-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="size-11 sm:size-12 rounded-full bg-osca-warning/15 text-osca-warning flex items-center justify-center shrink-0">
                                        <svg class="size-6 sm:size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2m-7.07-15.07 1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41m12.73-12.73-1.41 1.41"/></svg>
                                    </span>
                                    <div>
                                        <p class="text-2xl sm:text-3xl font-bold text-osca-ink leading-none">29&deg;C</p>
                                        <p class="text-xs text-osca-body mt-1">Partly Cloudy &middot; Fair Weather</p>
                                    </div>
                                </div>
                                <div class="text-left sm:text-right text-xs space-y-1 text-osca-body">
                                    <p>Humidity: <strong class="text-osca-ink">72%</strong></p>
                                    <p>Air Quality: <strong class="text-osca-success">Good</strong></p>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-osca-border text-xs flex items-center gap-2 text-osca-body">
                                <span class="size-2 rounded-full bg-osca-success shrink-0"></span>
                                <span>Ideal outdoor conditions for senior citizen counter visits.</span>
                            </div>
                        </div>

                        {{-- Service summary --}}
                        <div class="rounded-xl border border-osca-border border-t-4 border-t-osca-primary bg-white p-5 sm:p-6 shadow-xs">
                            <h4 class="text-sm font-bold text-osca-ink">OSCA Express Service Summary</h4>
                            <p class="text-xs text-osca-body mt-1">All service counters are operating today at the Santa Maria Municipal Hall Complex.</p>
                            <div class="mt-4 space-y-2.5 text-xs">
                                <div class="flex flex-wrap gap-x-3 gap-y-1 py-1 border-b border-osca-border">
                                    <span class="text-osca-body">Office Hours:</span>
                                    <span class="font-semibold text-osca-ink">Mon – Fri, 8:00 AM – 5:00 PM</span>
                                </div>
                                <div class="flex flex-wrap gap-x-3 gap-y-1 py-1 border-b border-osca-border">
                                    <span class="text-osca-body">Senior Express Lane:</span>
                                    <span class="font-semibold text-osca-success">Window 1 &amp; Window 2</span>
                                </div>
                                <div class="flex flex-wrap gap-x-3 gap-y-1 py-1">
                                    <span class="text-osca-body">Inquiry Helpline:</span>
                                    <span class="font-semibold text-osca-primary">(044) 913-0248</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer class="bg-osca-ink text-white/80 text-sm" aria-label="Page footer">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-10">
                <div class="sm:col-span-2 lg:col-span-5 space-y-4">
                    <div class="flex items-center gap-3">
                        <div>
                            <img src="{{ asset('images/eLINGAP.png') }}" alt="eLINGAP logo" class="h-14 w-20 object-contain object-left" width="80" height="56">
                            <span class="text-xs text-white/70 block">Office of the Senior Citizens Affairs</span>
                        </div>
                    </div>
                    <p class="text-xs sm:text-sm text-white/70 leading-relaxed max-w-sm">An integrated web-based records management and automated SMS notification system serving the senior citizens of Santa Maria, Bulacan.</p>
                    <p class="text-xs text-white/60">Republic of the Philippines &middot; Province of Bulacan &middot; Municipality of Santa Maria</p>
                </div>
                <div class="lg:col-span-3 space-y-3">
                    <h5 class="text-sm font-semibold uppercase tracking-wider text-white">Quick Navigation</h5>
                    <ul class="space-y-2 text-xs sm:text-sm text-white/70">
                        <li><a href="#hero" class="hover:text-white transition-colors block py-0.5">Home</a></li>
                        <li><a href="#programs" class="hover:text-white transition-colors block py-0.5">Programs &amp; Benefits</a></li>
                        <li><a href="#records" class="hover:text-white transition-colors block py-0.5">Records Management</a></li>
                        <li><a href="#sms" class="hover:text-white transition-colors block py-0.5">SMS Notifications</a></li>
                        <li><a href="#about" class="hover:text-white transition-colors block py-0.5">About OSCA Santa Maria</a></li>
                        <li><a href="#emergency" class="hover:text-white transition-colors block py-0.5">Emergency Hotlines</a></li>
                    </ul>
                </div>
                <div class="lg:col-span-4 space-y-3">
                    <h5 class="text-sm font-semibold uppercase tracking-wider text-white">Office of Senior Citizens Affairs</h5>
                    <div class="space-y-2.5 text-xs sm:text-sm text-white/70">
                        <p class="flex items-start gap-2.5">
                            <svg class="size-4 shrink-0 mt-0.5 text-white/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>Ground Floor, Municipal Hall Building, Poblacion, Santa Maria, Bulacan 3022</span>
                        </p>
                        <p class="flex items-center gap-2.5">
                            <svg class="size-4 shrink-0 text-white/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            <span class="min-w-0 break-words">osca@santamariabulacan.gov.ph</span>
                        </p>
                        <p class="flex items-center gap-2.5">
                            <svg class="size-4 shrink-0 text-white/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.88.37 1.85.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.96.33 1.93.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <span>(044) 913-0248</span>
                        </p>
                        <p class="text-xs text-white/50 pt-2 border-t border-white/10">Counter Hours: Monday to Friday &middot; 8:00 AM to 5:00 PM (Excluding Official Holidays)</p>
                    </div>
                </div>
            </div>
            <div class="mt-10 sm:mt-12 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-white/60 text-center sm:text-left">
                <p>&copy; {{ date('Y') }} Office of the Senior Citizens Affairs (OSCA) — Santa Maria, Bulacan. All rights reserved.</p>
                <div class="flex items-center gap-4">
                    <span>Data Privacy Act of 2012 Compliant</span>
                    <span>&middot;</span>
                    <a href="#hero" class="text-white hover:underline">Back to top &uarr;</a>
                </div>
            </div>
        </div>
    </footer>

    {{-- Search modal --}}
    <div id="search-modal" class="fixed inset-0 z-50 hidden bg-osca-ink/70 backdrop-blur-xs flex items-start justify-center p-3 sm:p-4 pt-16 sm:pt-20 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="search-modal-title">
        <div class="bg-white rounded-2xl border border-osca-border max-w-lg w-full p-5 shadow-xl max-h-[85vh] overflow-y-auto my-auto">
            <div class="flex items-center justify-between pb-3 border-b border-osca-border">
                <h3 id="search-modal-title" class="text-sm font-bold text-osca-ink">Search eLINGAP Portal</h3>
                <button type="button" data-close-modal="search" class="size-8 rounded-lg text-osca-body hover:bg-osca-muted flex items-center justify-center" aria-label="Close search">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="mt-4 relative">
                <input type="search" data-focus class="w-full rounded-lg border border-osca-border pl-10 pr-4 py-2.5 text-base sm:text-sm text-osca-ink focus:border-osca-primary focus:outline-hidden min-h-[44px]" placeholder="Search for programs, hotlines, benefits...">
                <svg class="size-4 absolute left-3.5 top-3.5 text-osca-body" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <div class="mt-4 pt-3 border-t border-osca-border">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-osca-body block mb-2">Quick Navigation</span>
                <div class="flex flex-wrap gap-2 text-xs">
                    <a href="#programs" data-close-modal="search" class="rounded-lg bg-osca-muted px-3 py-1.5 text-osca-ink hover:bg-osca-primary hover:text-white transition-colors">Social Pension</a>
                    <a href="#records" data-close-modal="search" class="rounded-lg bg-osca-muted px-3 py-1.5 text-osca-ink hover:bg-osca-primary hover:text-white transition-colors">Senior ID Registration</a>
                    <a href="#sms" data-close-modal="search" class="rounded-lg bg-osca-muted px-3 py-1.5 text-osca-ink hover:bg-osca-primary hover:text-white transition-colors">SMS Notifications</a>
                    <a href="#emergency" data-close-modal="search" class="rounded-lg bg-osca-muted px-3 py-1.5 text-osca-ink hover:bg-osca-primary hover:text-white transition-colors">Emergency Hotlines</a>
                    <a href="#announcements" data-close-modal="search" class="rounded-lg bg-osca-muted px-3 py-1.5 text-osca-ink hover:bg-osca-primary hover:text-white transition-colors">Payout Schedules</a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
