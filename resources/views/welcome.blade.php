<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>eLINGAP — Office of the Senior Citizens Affairs | Santa Maria, Bulacan</title>
    <link rel="icon" type="image/png" href="{{ asset('images/eLINGAP.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-osca-body antialiased">

    {{-- Primary navigation --}}
    <header class="bg-white border-b border-osca-border sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-18">
                {{-- Logo --}}
                <a href="#hero" data-view="home" class="flex items-center gap-2.5 shrink-0">
                    <img src="{{ asset('images/eLINGAP.png') }}" alt="eLINGAP logo" class="size-12 sm:size-14 object-contain" width="56" height="56">
                    <div>
                        <span class="text-xl sm:text-2xl font-bold tracking-tight text-osca-ink leading-none block">eLINGAP</span>
                        <span class="text-[10px] text-osca-body hidden xs:block font-medium">Santa Maria OSCA</span>
                    </div>
                </a>

                {{-- Desktop menu --}}
                <nav class="hidden xl:flex ml-auto items-center gap-6 2xl:gap-8 text-sm font-medium text-osca-body" aria-label="Main menu">
                    <a href="#hero" data-view="home" data-view-link class="hover:text-osca-primary transition-colors cursor-pointer">Home</a>
                    <a href="#about-view" data-view="about" data-view-link class="hover:text-osca-primary transition-colors cursor-pointer">About eLINGAP</a>
                    <a href="mailto:osca@santamariabulacan.gov.ph" class="hover:text-osca-primary transition-colors cursor-pointer">Contact</a>
                </nav>

                {{-- Right controls --}}
                <div class="flex items-center gap-3">
                    <button type="button" id="mobile-menu-btn" class="size-11 min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg text-osca-ink xl:hidden hover:bg-osca-muted transition-colors" aria-label="Toggle menu" aria-expanded="false">
                        <svg id="menu-icon-bars" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                        <svg id="menu-icon-close" class="size-6 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile drawer --}}
        <nav id="mobile-menu" class="hidden xl:hidden border-t border-osca-border bg-white px-4 pt-3 pb-6 space-y-1.5 max-h-[calc(100vh-4.5rem)] overflow-y-auto" aria-label="Mobile menu">
            <a href="#hero" data-view="home" data-view-link class="block rounded-lg px-3.5 py-3 text-base font-medium text-osca-ink hover:bg-osca-muted">Home</a>
            <a href="#about-view" data-view="about" data-view-link class="block rounded-lg px-3.5 py-3 text-base font-medium text-osca-ink hover:bg-osca-muted">About eLINGAP</a>
            <a href="mailto:osca@santamariabulacan.gov.ph" class="block rounded-lg px-3.5 py-3 text-base font-medium text-osca-ink hover:bg-osca-muted">Contact</a>
        </nav>
    </header>

    <main>
        <div id="landing-view">
        {{-- Light hero band --}}
        <section id="hero" class="relative overflow-hidden pt-10 pb-20 sm:pt-14 sm:pb-24 lg:pt-16 lg:pb-28">
            {{-- Decorative organic shapes --}}
            <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
                <div class="absolute -right-20 top-10 size-80 rounded-full border border-osca-primary/10"></div>
                <div class="absolute right-36 top-28 size-48 rounded-full bg-osca-primary/5"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-stretch">
                    {{-- Copy column --}}
                    <div class="lg:col-span-8 max-w-4xl space-y-5 sm:space-y-6">
                        <span class="text-xs font-semibold uppercase tracking-[0.2em] text-osca-body">Welcome to eLINGAP</span>
                        <h1 class="text-3xl sm:text-4xl lg:text-[46px] xl:text-[50px] font-bold text-osca-ink tracking-tight leading-[1.15] sm:leading-[1.1]">
                            Caring for Santa Maria's <span class="text-osca-primary">Senior Citizens</span>
                        </h1>
                        <p class="text-sm sm:text-base lg:text-lg leading-relaxed max-w-2xl">
                            eLINGAP is the official records management and automated SMS notification system of the Office of the Senior Citizens Affairs (OSCA) in the Municipality of Santa Maria, Bulacan. We modernize senior citizen welfare services through an accurate digital registry, paperless status tracking, and direct communication to every household.
                        </p>
                        <div class="pt-1 pb-1">
                            <button type="button" data-open-modal="login" aria-haspopup="dialog" aria-controls="login-modal" class="inline-flex items-center justify-center gap-2 rounded-lg bg-osca-primary px-7 py-3.5 text-sm font-semibold text-white hover:bg-osca-primary-dark transition-colors shadow-md min-h-[44px] cursor-pointer">
                                Log In
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 3-Up Feature Cards --}}
        <section class="py-12 sm:py-16 bg-osca-muted/40 border-t border-osca-border" aria-label="Core Services">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6 lg:gap-8">
                    {{-- Card 1: Benefits & Programs --}}
                    <article class="bg-white rounded-xl border border-slate-200 p-6 sm:p-7 shadow-sm">
                        <div>
                            <div class="size-12 rounded-lg bg-osca-primary/10 text-osca-primary flex items-center justify-center mb-4">
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"/><path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-osca-ink">Benefits &amp; Programs</h3>
                            <p class="mt-2 text-sm text-osca-body leading-relaxed">Track quarterly social pension distributions, local merchant discounts, medical support subsidies, and funeral grants.</p>
                        </div>
                    </article>

                    {{-- Card 2: Records Management --}}
                    <article class="bg-white rounded-xl border border-slate-200 p-6 sm:p-7 shadow-sm">
                        <div>
                            <div class="size-12 rounded-lg bg-osca-primary/10 text-osca-primary flex items-center justify-center mb-4">
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-osca-ink">Records Management</h3>
                            <p class="mt-2 text-sm text-osca-body leading-relaxed">Unified digital senior registry across all 24 barangays for instant ID validation, status updates, and caregiver links.</p>
                        </div>
                    </article>

                    {{-- Card 3: SMS Notifications --}}
                    <article class="bg-white rounded-xl border border-slate-200 p-6 sm:p-7 shadow-sm">
                        <div>
                            <div class="size-12 rounded-lg bg-osca-primary/10 text-osca-primary flex items-center justify-center mb-4">
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 10h.01"/><path d="M12 10h.01"/><path d="M16 10h.01"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-osca-ink">SMS Notifications</h3>
                            <p class="mt-2 text-sm text-osca-body leading-relaxed">Automated, reliable text alerts informing seniors and families of payout dates, barangay assemblies, and health missions.</p>
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
                <div class="w-full">
                    <div class="w-full space-y-5">
                        <span class="text-xs font-semibold uppercase tracking-wider text-osca-primary">Barangay-Synchronized Registry</span>
                        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-osca-ink tracking-tight">
                            Digital Senior Citizen <span class="text-osca-primary">Records Registry</span>
                        </h2>
                        <div class="w-16 h-1 bg-osca-primary rounded-full mt-3" aria-hidden="true"></div>
                        <p class="text-sm sm:text-base text-osca-body leading-relaxed">
                            eLINGAP provides unified records verification across Santa Maria's 24 barangays, accelerating ID renewals, benefit validation, and family caregiver contacts.
                        </p>
                        <div class="w-full space-y-3 pt-2">
                            <div class="w-full flex items-start gap-3 p-3.5 rounded-xl bg-osca-muted border border-osca-border">
                                <span class="size-6 rounded-full bg-osca-success text-white flex items-center justify-center shrink-0 mt-0.5 text-xs font-bold">&check;</span>
                                <div>
                                    <h4 class="text-xs sm:text-sm font-bold text-osca-ink">Unified 24-Barangay Masterlist</h4>
                                    <p class="text-xs text-osca-body mt-0.5">Centralized database syncs senior records from Poblacion to Tumana in real-time.</p>
                                </div>
                            </div>
                            <div class="w-full flex items-start gap-3 p-3.5 rounded-xl bg-osca-muted border border-osca-border">
                                <span class="size-6 rounded-full bg-osca-success text-white flex items-center justify-center shrink-0 mt-0.5 text-xs font-bold">&check;</span>
                                <div>
                                    <h4 class="text-xs sm:text-sm font-bold text-osca-ink">Caregiver &amp; Emergency Contact Link</h4>
                                    <p class="text-xs text-osca-body mt-0.5">Automated linking of next-of-kin mobile numbers ensures families stay notified.</p>
                                </div>
                            </div>
                            <div class="w-full flex items-start gap-3 p-3.5 rounded-xl bg-osca-muted border border-osca-border">
                                <span class="size-6 rounded-full bg-osca-success text-white flex items-center justify-center shrink-0 mt-0.5 text-xs font-bold">&check;</span>
                                <div>
                                    <h4 class="text-xs sm:text-sm font-bold text-osca-ink">Instant ID &amp; Pension Validation</h4>
                                    <p class="text-xs text-osca-body mt-0.5">OSCA counter staff verify eligibility within seconds with zero paper bottlenecks.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        {{-- SMS Notifications Section --}}
        <section id="sms" class="scroll-mt-20 py-14 sm:py-20 bg-white text-osca-ink border-t border-slate-200" aria-label="SMS Notifications">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-start">
                    <div class="lg:col-span-7 space-y-5">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Automated Mobile Alert Dispatcher</span>
                        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-slate-900">
                            Direct SMS Alerts Sent Straight to <span class="text-blue-600">Seniors &amp; Families</span>
                        </h2>
                        <div class="w-16 h-1 bg-osca-primary rounded-full mt-3" aria-hidden="true"></div>
                        <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                            Never miss an assembly, medicine distribution, or pension release date. eLINGAP sends automatic SMS reminders directly to registered mobile phones.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 pt-2">
                            <div class="bg-[#0B3B75] rounded-xl border border-blue-700/40 p-4 text-center shadow-sm">
                                <p class="text-2xl sm:text-3xl font-bold text-white">18,400+</p>
                                <p class="text-xs text-blue-200 mt-1">Seniors Reached</p>
                            </div>
                            <div class="bg-[#0B3B75] rounded-xl border border-blue-700/40 p-4 text-center shadow-sm">
                                <p class="text-2xl sm:text-3xl font-bold text-white">98.6%</p>
                                <p class="text-xs text-blue-200 mt-1">Delivery Success</p>
                            </div>
                            <div class="bg-[#0B3B75] rounded-xl border border-blue-700/40 p-4 text-center shadow-sm">
                                <p class="text-2xl sm:text-3xl font-bold text-white">100% Free</p>
                                <p class="text-xs text-blue-200 mt-1">Free Public Service</p>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-5 flex justify-center lg:mt-10">
                        <div class="w-full max-w-sm bg-[#2B2D2F] border border-neutral-700 rounded-2xl p-5 shadow-lg" style="margin-top: 3.5rem !important;">
                            <div class="flex items-center justify-between border-b border-neutral-700 pb-2.5 text-xs text-slate-400">
                                <span class="font-bold text-slate-200">OSCA-SANTA-MARIA</span>
                                <span class="text-slate-400">Official SMS</span>
                            </div>
                            <div class="mt-4 space-y-3 text-xs">
                                <div class="bg-[#383A3C] rounded-2xl rounded-tl-none p-4 text-slate-100 leading-relaxed border border-neutral-700">
                                    <p class="font-bold text-amber-400 text-[11px] uppercase tracking-wide mb-1">Paunawa sa Social Pension:</p>
                                    <p>Magandang araw po! Ang 3rd Quarter Social Pension payout (P3,000) ay gaganapin sa Oct 12 sa Santa Maria Municipal Gym mula 8:00 AM.</p>
                                    <p class="text-[10px] text-slate-300 mt-2">Mangyaring dalhin ang inyong OSCA ID at booklet.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Leadership Band --}}
        <section class="bg-white text-osca-ink py-16 sm:py-24 relative overflow-hidden border-t border-osca-border" aria-label="Leadership and Mandate">
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-7 space-y-5">
                        <h2 class="text-3xl sm:text-4xl font-bold tracking-tight">Dedicated to the Dignity and Welfare of Our Elders</h2>
                        <div class="w-16 h-1 bg-osca-primary rounded-full" aria-hidden="true"></div>
                        <p class="text-[15px] sm:text-base text-osca-body leading-relaxed">
                            Under Republic Act No. 7432 and Republic Act No. 9994 (Expanded Senior Citizens Act), the Office of the Senior Citizens Affairs of Santa Maria works to advocate for the rights, privileges, and overall welfare of our elderly. eLINGAP modernizes this mandate through transparent records and automated outreach.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                            <div class="flex items-start gap-3">
                                <span class="size-6 rounded-full bg-osca-primary/10 text-osca-primary flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="text-sm font-medium text-osca-ink">Health &amp; Wellness Programs</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="size-6 rounded-full bg-osca-primary/10 text-osca-primary flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="text-sm font-medium text-osca-ink">Direct Benefit &amp; Pension Access</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="size-6 rounded-full bg-osca-primary/10 text-osca-primary flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="text-sm font-medium text-osca-ink">Community &amp; Barangay Engagement</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="size-6 rounded-full bg-osca-primary/10 text-osca-primary flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="text-sm font-medium text-osca-ink">Full Records Digitization</span>
                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-5">
                        <div class="rounded-xl border border-slate-200 bg-white p-7 sm:p-8 shadow-sm">
                            <div class="size-16 rounded-full bg-osca-primary/10 border border-osca-primary/15 flex items-center justify-center text-xl font-bold mb-5 text-osca-primary">OSCA</div>
                            <blockquote class="text-base sm:text-lg text-osca-body italic leading-relaxed border-l-4 border-osca-primary pl-4">
                                &ldquo;Our senior citizens are the pillars of Santa Maria's heritage. Ensuring their well-being, dignity, and prompt access to public services is our highest duty.&rdquo;
                            </blockquote>
                            <div class="mt-6 pt-4 border-t border-osca-border">
                                <p class="font-bold text-base text-osca-ink">Office of the Senior Citizens Affairs</p>
                                <p class="text-xs text-osca-body">Municipal Government of Santa Maria, Bulacan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Announcements --}}
        <section id="announcements" class="scroll-mt-20 py-14 sm:py-20 bg-osca-muted" aria-label="Announcements">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="w-full">
                    <div class="w-full space-y-4">
                        <div class="mb-2">
                            <span class="text-xs font-semibold uppercase tracking-wider text-osca-primary">Community Updates</span>
                            <h3 class="text-2xl sm:text-3xl font-bold text-osca-ink mt-1">Upcoming Distribution Schedules</h3>
                            <div class="w-16 h-1 bg-osca-primary rounded-full mt-3" aria-hidden="true"></div>
                            <div class="w-full flex items-center justify-between mt-3">
                                <span class="text-xs sm:text-sm text-osca-body">Municipal Payouts &amp; Assemblies</span>
                                <span class="text-xs font-semibold text-osca-primary bg-osca-primary/10 px-3 py-1 rounded-full">2026 Calendar</span>
                            </div>
                        </div>

                        {{-- Schedule Card 1 --}}
                        <div class="w-full rounded-xl border border-slate-200 bg-white p-4 sm:p-5 flex flex-col sm:flex-row items-start gap-3.5 sm:gap-4 shadow-xs">
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
                        <div class="w-full rounded-xl border border-slate-200 bg-white p-4 sm:p-5 flex flex-col sm:flex-row items-start gap-3.5 sm:gap-4 shadow-xs">
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
                        <div class="w-full rounded-xl border border-slate-200 bg-white p-4 sm:p-5 flex flex-col sm:flex-row items-start gap-3.5 sm:gap-4 shadow-xs">
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

                </div>
            </div>
        </section>
        </div>

        <x-about-elingap />
    </main>

    {{-- Footer --}}
    <footer class="bg-osca-ink text-white/80 text-sm" aria-label="Page footer">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div>
                            <img src="{{ asset('images/eLINGAP.png') }}" alt="eLINGAP logo" class="h-14 w-20 object-contain object-left" width="80" height="56">
                            <span class="text-xs text-white/70 block">Office of the Senior Citizens Affairs</span>
                        </div>
                    </div>
                    <p class="text-xs sm:text-sm text-white/70 leading-relaxed max-w-sm">An integrated web-based records management and automated SMS notification system serving the senior citizens of Santa Maria, Bulacan.</p>
                </div>
                <div class="space-y-3 md:mt-6">
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
            <div class="mt-10 sm:mt-12 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-white/60 text-center sm:text-left">
                <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-4">
                    <p>&copy; {{ date('Y') }} Office of the Senior Citizens Affairs (OSCA) — Santa Maria, Bulacan. All rights reserved.</p>
                    <span class="hidden sm:inline">&middot;</span>
                    <span>Data Privacy Act of 2012 Compliant</span>
                </div>
                <button type="button" data-back-to-top class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-1.5 text-xs sm:text-sm font-medium text-slate-100 hover:bg-neutral-700 hover:text-white transition-colors duration-150" aria-label="Back to top">Back to top &uarr;</button>
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
                    <a href="#announcements" data-close-modal="search" class="rounded-lg bg-osca-muted px-3 py-1.5 text-osca-ink hover:bg-osca-primary hover:text-white transition-colors">Payout Schedules</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Login modal --}}
    <div id="login-modal" class="fixed inset-0 z-50 {{ $errors->any() ? '' : 'hidden' }} bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="login-modal-title" aria-describedby="login-modal-description">
        <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 sm:p-7 shadow-2xl my-auto">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-blue-700">eLINGAP Portal</span>
                    <h2 id="login-modal-title" class="mt-4 text-2xl font-bold tracking-tight text-slate-900">Sign In to eLINGAP</h2>
                    <p id="login-modal-description" class="mt-1.5 text-sm leading-relaxed text-slate-500">Enter your authorized OSCA credentials or account details.</p>
                </div>
                <button type="button" data-close-modal="login" class="size-10 shrink-0 inline-flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 transition-colors" aria-label="Close login dialog">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <form id="login-form" class="mt-6 space-y-4" action="{{ route('login') }}" method="post">
                @csrf
                @if ($errors->any())
                    <p id="login-error" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700" role="alert">
                        {{ $errors->first() }}
                    </p>
                @else
                    <p id="login-error" class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700" role="alert"></p>
                @endif
                <div>
                    <label for="login-identity" class="block text-sm font-semibold text-slate-700">Email or Username</label>
                    <div class="relative mt-1.5">
                        <svg class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                        <input id="login-identity" name="email" type="text" value="{{ old('email') }}" autocomplete="username" data-focus required class="w-full rounded-lg border border-slate-300 bg-slate-50 py-3 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20" placeholder="Email or username" aria-describedby="login-error">
                    </div>
                </div>

                <div>
                    <label for="login-password" class="block text-sm font-semibold text-slate-700">Password</label>
                    <div class="relative mt-1.5">
                        <svg class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="16" height="12" x="4" y="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        <input id="login-password" name="password" type="password" autocomplete="current-password" required class="w-full rounded-lg border border-slate-300 bg-slate-50 py-3 pl-10 pr-12 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20" placeholder="Enter your password" aria-describedby="login-error">
                        <button type="button" data-toggle-password="login-password" class="absolute right-2 top-1/2 size-9 -translate-y-1/2 inline-flex items-center justify-center rounded-md text-slate-500 hover:bg-slate-200 hover:text-slate-900 transition-colors" aria-label="Show password" aria-pressed="false">
                            <svg data-eye-icon class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.06 12.35a1 1 0 0 1 0-.7C3.32 8.07 7.26 5 12 5s8.68 3.07 9.94 6.65a1 1 0 0 1 0 .7C20.68 15.93 16.74 19 12 19s-8.68-3.07-9.94-6.65Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-1 text-sm">
                    <label class="inline-flex items-center gap-2 text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-600" @checked(old('remember'))>
                        <span>Remember me</span>
                    </label>
                    <a href="mailto:osca@santamariabulacan.gov.ph?subject=Password%20Reset" class="font-semibold text-blue-600 hover:underline">Forgot password?</a>
                </div>

                <button id="login-submit" type="submit" class="w-full rounded-lg bg-[#0B3B75] px-4 py-3 text-sm font-semibold text-white hover:bg-blue-800 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">
                    <span data-login-label>Log In</span>
                    <span data-login-loading class="hidden items-center justify-center gap-2">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path></svg>
                        <span>Signing in...</span>
                    </span>
                </button>
                <p class="text-center text-xs leading-relaxed text-slate-500">Need an account or first-time setup? Contact OSCA frontline desk or your Barangay coordinator.</p>
            </form>
        </div>
    </div>

</body>
</html>
