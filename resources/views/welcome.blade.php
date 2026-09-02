<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>eLINGAP — Office of the Senior Citizens Affairs | Santa Maria, Bulacan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-osca-body antialiased selection:bg-osca-primary selection:text-white">

    <!-- Utility bar (dark) -->
    <aside class="bg-osca-ink text-white text-xs border-b border-white/10" aria-label="Official announcements and contact">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div class="flex items-center gap-3 overflow-x-auto text-[13px]">
                <span class="font-medium text-white/70 whitespace-nowrap">{{ now()->format('l, F j, Y') }}</span>
                <span class="hidden md:inline text-white/30">|</span>
                <div class="flex items-center gap-2 whitespace-nowrap">
                    <span class="rounded bg-osca-primary px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-white">Notice</span>
                    <span class="text-white/90">3rd Quarter Social Pension payout begins October 12 at Santa Maria Municipal Gymnasium.</span>
                </div>
            </div>
            <div class="flex items-center gap-4 text-[13px] text-white/80 whitespace-nowrap self-end md:self-auto">
                <a href="mailto:osca@santamariabulacan.gov.ph" class="hover:text-white transition-colors flex items-center gap-1.5">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    <span>osca@santamariabulacan.gov.ph</span>
                </a>
                <span class="hidden sm:inline text-white/30">|</span>
                <a href="tel:0449130248" class="hidden sm:flex items-center gap-1.5 hover:text-white transition-colors">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <span>(044) 913-0248</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Navigation (white background) -->
    <header class="bg-white border-b border-osca-border sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Logo -->
                <a href="#hero" class="flex items-center gap-3 group focus:outline-hidden" aria-label="eLINGAP Home">
                    <span class="flex size-11 items-center justify-center rounded-lg bg-osca-primary text-white font-bold text-xl shadow-xs transition-colors group-hover:bg-osca-primary-dark">
                        eL
                    </span>
                    <div class="flex flex-col">
                        <span class="text-2xl font-bold tracking-tight text-osca-primary leading-none">eLINGAP</span>
                        <span class="text-xs font-medium text-osca-ink mt-1">Office of the Senior Citizens Affairs</span>
                        <span class="text-[11px] text-osca-body">Santa Maria, Bulacan</span>
                    </div>
                </a>

                <!-- Desktop menu -->
                <nav class="hidden lg:flex items-center gap-7 text-sm font-medium text-osca-ink" aria-label="Main menu">
                    <a href="#hero" class="text-osca-primary font-semibold border-b-2 border-osca-primary pb-1">Home</a>
                    <a href="#programs" class="hover:text-osca-primary transition-colors pb-1">Programs &amp; Benefits</a>
                    <a href="#records" class="hover:text-osca-primary transition-colors pb-1">Records</a>
                    <a href="#sms-notifications" class="hover:text-osca-primary transition-colors pb-1">SMS Notifications</a>
                    <a href="#about" class="hover:text-osca-primary transition-colors pb-1">About OSCA</a>
                    <a href="#emergency" class="hover:text-osca-primary transition-colors pb-1">Contact</a>
                </nav>

                <!-- Right controls -->
                <div class="flex items-center gap-3">
                    <button type="button" id="search-open-btn" class="inline-flex size-10 items-center justify-center rounded-lg border border-osca-border text-osca-body hover:text-osca-ink hover:bg-osca-muted transition-colors" aria-label="Search site">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </button>
                    <button type="button" id="concern-open-btn" class="hidden sm:inline-flex items-center justify-center rounded-lg bg-osca-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-osca-primary-dark transition-colors shadow-xs">
                        File a Concern
                    </button>
                    <button type="button" id="mobile-menu-btn" class="inline-flex size-10 items-center justify-center rounded-lg border border-osca-border text-osca-ink lg:hidden hover:bg-osca-muted transition-colors" aria-label="Toggle navigation menu" aria-expanded="false">
                        <svg id="menu-icon-bars" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                        <svg id="menu-icon-close" class="size-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Drawer -->
        <nav id="mobile-menu" class="hidden lg:hidden border-t border-osca-border bg-white px-4 pt-3 pb-6 space-y-2 text-sm" aria-label="Mobile menu">
            <a href="#hero" class="block rounded-lg px-3 py-2.5 font-semibold text-osca-primary bg-osca-muted">Home</a>
            <a href="#programs" class="block rounded-lg px-3 py-2.5 font-medium text-osca-ink hover:bg-osca-muted">Programs &amp; Benefits</a>
            <a href="#records" class="block rounded-lg px-3 py-2.5 font-medium text-osca-ink hover:bg-osca-muted">Records</a>
            <a href="#sms-notifications" class="block rounded-lg px-3 py-2.5 font-medium text-osca-ink hover:bg-osca-muted">SMS Notifications</a>
            <a href="#about" class="block rounded-lg px-3 py-2.5 font-medium text-osca-ink hover:bg-osca-muted">About OSCA</a>
            <a href="#emergency" class="block rounded-lg px-3 py-2.5 font-medium text-osca-ink hover:bg-osca-muted">Contact &amp; Emergency</a>
            <div class="pt-3 border-t border-osca-border flex flex-col gap-2">
                <button type="button" id="mobile-concern-btn" class="w-full text-center rounded-lg bg-osca-primary px-4 py-2.5 font-semibold text-white hover:bg-osca-primary-dark">
                    File a Concern
                </button>
            </div>
        </nav>
    </header>

    <main id="main-content">
        <!-- NAVY HERO BAND -->
        <section id="hero" class="relative bg-osca-primary text-white overflow-hidden pt-12 pb-24 lg:pt-16 lg:pb-32">
            <!-- Background organic shapes and geometric accents -->
            <div class="absolute inset-0 pointer-events-none opacity-20" aria-hidden="true">
                <svg class="absolute -right-24 -top-24 size-[520px] text-osca-primary-dark" viewBox="0 0 400 400" fill="currentColor">
                    <path d="M320,190Q300,280,210,310Q120,340,90,240Q60,140,150,90Q240,40,320,190Z" />
                </svg>
                <div class="absolute left-1/4 bottom-0 size-96 rounded-full border border-white/10"></div>
                <div class="absolute left-1/4 bottom-12 size-72 rounded-full border border-white/10"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <!-- Left Content -->
                    <div class="lg:col-span-7 space-y-6">
                        <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1 text-[13px] font-medium text-white/90 backdrop-blur-xs">
                            <span class="size-2 rounded-full bg-osca-warning"></span>
                            <span>Welcome to eLINGAP</span>
                        </div>
                        <h1 class="text-4xl sm:text-5xl lg:text-[46px] font-bold text-white tracking-tight leading-[1.1]">
                            Caring for Santa Maria's <span class="text-white underline decoration-white/40 underline-offset-8">Senior Citizens</span>
                        </h1>
                        <p class="text-base sm:text-lg text-white/85 leading-relaxed max-w-2xl">
                            An integrated web-based records management and automated SMS notification system for the Office of the Senior Citizens Affairs of Santa Maria, Bulacan.
                        </p>
                        <div class="flex flex-wrap items-center gap-3 pt-2">
                            <a href="#records" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-6 py-3 text-sm font-semibold text-osca-primary hover:bg-osca-muted transition-colors shadow-xs">
                                Register Now
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                            <a href="#programs" class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/30 bg-white/5 px-6 py-3 text-sm font-medium text-white hover:bg-white/10 transition-colors">
                                Check Benefit Status
                            </a>
                        </div>

                        <!-- Slider Variant Indicator Controls -->
                        <div class="pt-6 flex items-center gap-4 text-xs text-white/70">
                            <div class="flex items-center gap-1.5">
                                <span class="size-2 rounded-full bg-white"></span>
                                <span class="size-2 rounded-full bg-white/30"></span>
                                <span class="size-2 rounded-full bg-white/30"></span>
                            </div>
                            <span>01 / 03 · Public Senior Welfare &amp; Records Digitization</span>
                            <div class="flex items-center gap-1 ml-auto">
                                <button type="button" class="size-8 rounded border border-white/20 flex items-center justify-center text-white/80 hover:bg-white/10 hover:text-white" aria-label="Previous slide">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                                </button>
                                <button type="button" class="size-8 rounded border border-white/20 flex items-center justify-center text-white/80 hover:bg-white/10 hover:text-white" aria-label="Next slide">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right Portrait & Organic Shape Backdrop -->
                    <div class="lg:col-span-5 relative flex justify-center">
                        <div class="relative w-full max-w-md">
                            <!-- Organic Navy shape backdrop -->
                            <div class="absolute inset-0 bg-osca-primary-dark/60 rounded-3xl transform rotate-1 scale-105 filter blur-xs" aria-hidden="true"></div>
                            
                            <!-- Hero Card Container -->
                            <div class="relative rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 p-6 sm:p-8 text-white shadow-xl">
                                <div class="flex items-center gap-4 border-b border-white/15 pb-5">
                                    <div class="size-14 rounded-full bg-white/20 flex items-center justify-center text-2xl font-bold text-white shrink-0 shadow-inner">
                                        <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    </div>
                                    <div>
                                        <span class="text-xs uppercase tracking-wider text-white/70 font-semibold">OSCA Frontline Service</span>
                                        <h2 class="text-xl font-bold text-white">Santa Maria Senior Center</h2>
                                        <p class="text-xs text-white/80">Serving 24 Barangays of Bulacan</p>
                                    </div>
                                </div>

                                <div class="mt-5 space-y-3.5 text-sm">
                                    <div class="rounded-lg bg-white/10 p-3.5 flex items-start gap-3 border border-white/10">
                                        <span class="size-7 rounded-full bg-osca-success flex items-center justify-center shrink-0 text-white mt-0.5">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                        </span>
                                        <div>
                                            <p class="font-semibold text-white">Verified Senior Registry</p>
                                            <p class="text-xs text-white/75 mt-0.5">Automated validation of senior IDs, eligibility, and records.</p>
                                        </div>
                                    </div>

                                    <div class="rounded-lg bg-white/10 p-3.5 flex items-start gap-3 border border-white/10">
                                        <span class="size-7 rounded-full bg-white text-osca-primary flex items-center justify-center shrink-0 mt-0.5">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                        </span>
                                        <div>
                                            <p class="font-semibold text-white">Direct SMS Broadcasts</p>
                                            <p class="text-xs text-white/75 mt-0.5">Instant alerts for pension schedules sent straight to seniors' phones.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 pt-4 border-t border-white/15 flex items-center justify-between text-xs text-white/85">
                                    <span>Republic Act No. 9994 Compliance</span>
                                    <span class="font-semibold text-white">Active Portal</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FEATURE CARDS (3-up overlapping hero bottom edge) -->
        <section class="relative -mt-14 sm:-mt-16 z-20" aria-label="Core Services">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Feature Card 1: Benefits -->
                    <article id="programs" class="bg-white rounded-lg border border-osca-border p-6 sm:p-7 shadow-xs hover:border-osca-primary/50 transition-all flex flex-col justify-between">
                        <div>
                            <div class="size-12 rounded-full bg-osca-primary/10 text-osca-primary flex items-center justify-center mb-5">
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"/><path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z"/></svg>
                            </div>
                            <h3 class="text-lg sm:text-xl font-semibold text-osca-ink">Benefits &amp; Programs</h3>
                            <p class="mt-2 text-[15px] text-osca-body leading-relaxed">
                                Track quarterly social pension distributions, local merchant discounts, medical support subsidies, and funeral grants.
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-osca-border">
                            <a href="#emergency" class="inline-flex items-center gap-1.5 text-sm font-medium text-osca-primary hover:underline">
                                <span>Read More</span>
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                        </div>
                    </article>

                    <!-- Feature Card 2: Records -->
                    <article id="records" class="bg-white rounded-lg border border-osca-border p-6 sm:p-7 shadow-xs hover:border-osca-primary/50 transition-all flex flex-col justify-between">
                        <div>
                            <div class="size-12 rounded-full bg-osca-primary/10 text-osca-primary flex items-center justify-center mb-5">
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </div>
                            <h3 class="text-lg sm:text-xl font-semibold text-osca-ink">Records Management</h3>
                            <p class="mt-2 text-[15px] text-osca-body leading-relaxed">
                                Unified digital senior registry across all 24 barangays for instant ID validation, status updates, and caregiver links.
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-osca-border">
                            <a href="#about" class="inline-flex items-center gap-1.5 text-sm font-medium text-osca-primary hover:underline">
                                <span>Read More</span>
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                        </div>
                    </article>

                    <!-- Feature Card 3: SMS Notifications -->
                    <article id="sms-notifications" class="bg-white rounded-lg border border-osca-border p-6 sm:p-7 shadow-xs hover:border-osca-primary/50 transition-all flex flex-col justify-between">
                        <div>
                            <div class="size-12 rounded-full bg-osca-primary/10 text-osca-primary flex items-center justify-center mb-5">
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 10h.01"/><path d="M12 10h.01"/><path d="M16 10h.01"/></svg>
                            </div>
                            <h3 class="text-lg sm:text-xl font-semibold text-osca-ink">SMS Notifications</h3>
                            <p class="mt-2 text-[15px] text-osca-body leading-relaxed">
                                Automated, reliable text alerts informing seniors and families of payout dates, barangay assemblies, and health missions.
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-osca-border">
                            <a href="#announcements" class="inline-flex items-center gap-1.5 text-sm font-medium text-osca-primary hover:underline">
                                <span>Read More</span>
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- WELCOME TO eLINGAP BLOCK: CONTENT + MEDIA BLOCK -->
        <section id="about" class="py-16 sm:py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <!-- Left Column: Copy & Checklist -->
                    <div class="lg:col-span-7 space-y-6">
                        <div class="inline-flex items-center gap-2 text-[13px] font-medium text-osca-primary">
                            <span class="size-2 rounded-full bg-osca-primary"></span>
                            <span>About the System</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-osca-ink tracking-tight">
                            Welcome to <span class="text-osca-primary">eLINGAP</span>
                        </h2>
                        <p class="text-[15px] sm:text-base text-osca-body leading-relaxed">
                            eLINGAP is the official records management and automated SMS notification system of the Office of the Senior Citizens Affairs (OSCA) in the Municipality of Santa Maria, Bulacan. We modernize senior citizen welfare services by providing an accurate digital registry, paperless status tracking, and direct communication to every household.
                        </p>

                        <!-- Checklist (services offered) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-2">
                            <div class="flex items-start gap-3">
                                <span class="size-5 rounded-full bg-osca-primary/10 text-osca-primary flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="text-sm font-medium text-osca-ink">Online Registration &amp; Verification</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="size-5 rounded-full bg-osca-primary/10 text-osca-primary flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="text-sm font-medium text-osca-ink">Benefit Tracking &amp; Distribution</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="size-5 rounded-full bg-osca-primary/10 text-osca-primary flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="text-sm font-medium text-osca-ink">Automated SMS Reminders</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="size-5 rounded-full bg-osca-primary/10 text-osca-primary flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="text-sm font-medium text-osca-ink">24-Barangay Records Sync</span>
                            </div>
                        </div>

                        <!-- Signature line -->
                        <div class="pt-6 border-t border-osca-border flex items-center gap-4">
                            <div class="size-12 rounded-full bg-osca-muted border border-osca-border flex items-center justify-center text-osca-primary font-bold">
                                OSCA
                            </div>
                            <div>
                                <p class="text-sm font-bold text-osca-ink">Office of the Senior Citizens Affairs</p>
                                <p class="text-xs text-osca-body">Municipal Hall Complex, Santa Maria, Bulacan</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Media Presentation Card -->
                    <div class="lg:col-span-5">
                        <div class="relative rounded-lg border border-osca-border bg-osca-muted overflow-hidden shadow-xs">
                            <div class="aspect-4/3 bg-osca-primary-dark/90 relative flex items-center justify-center p-8 text-center">
                                <!-- Subtle decorative background pattern -->
                                <div class="absolute inset-0 opacity-15 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:16px_16px]"></div>
                                
                                <div class="relative z-10 space-y-4">
                                    <div class="size-16 rounded-full bg-white text-osca-primary mx-auto flex items-center justify-center shadow-lg hover:scale-105 transition-transform cursor-pointer" title="Play presentation video" aria-label="Play presentation video">
                                        <svg class="size-7 ml-1" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                    </div>
                                    <div class="text-white">
                                        <p class="font-bold text-lg">System Walkthrough &amp; Guide</p>
                                        <p class="text-xs text-white/80 mt-1">How eLINGAP serves Santa Maria's seniors</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 bg-white border-t border-osca-border flex items-center justify-between text-xs">
                                <span class="font-medium text-osca-ink">Community Outreach &amp; Orientation</span>
                                <span class="text-osca-primary font-semibold">Duration: 2 mins</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- OSCA EMERGENCY & HOTLINE NUMBERS -->
        <section id="emergency" class="py-16 sm:py-20 bg-osca-muted border-y border-osca-border">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-10 text-left">
                    <div class="inline-flex items-center gap-2 text-[13px] font-medium text-osca-primary">
                        <span class="size-2 rounded-full bg-osca-danger"></span>
                        <span>Immediate Assistance</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-osca-ink tracking-tight mt-1">
                        OSCA Emergency &amp; <span class="text-osca-primary">Hotline Numbers</span>
                    </h2>
                    <p class="mt-2 text-[15px] text-osca-body max-w-2xl">
                        Direct contact lines for senior citizens and family caregivers during medical emergencies, urgent queries, or disaster rescue.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
                    <!-- Callout Card Variant: Highlighted OSCA hotline on navy tile -->
                    <div class="lg:col-span-5 bg-osca-primary text-white rounded-lg p-7 sm:p-8 flex flex-col justify-between shadow-xs">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="size-11 rounded-full bg-white/15 flex items-center justify-center text-white">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                </span>
                                <span class="rounded bg-white/20 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-white">Dedicated Helpdesk</span>
                            </div>
                            <h3 class="text-xl font-bold text-white mt-5">OSCA Santa Maria Central Hotline</h3>
                            <p class="mt-2 text-sm text-white/80 leading-relaxed">
                                Operating Monday to Friday, 8:00 AM – 5:00 PM for record updates, benefit inquiries, and home visitation requests.
                            </p>

                            <div class="mt-6 p-4 rounded-lg bg-white/10 border border-white/15">
                                <span class="text-xs uppercase tracking-wider text-white/70 block">Direct Landline</span>
                                <a href="tel:0449130248" class="text-2xl sm:text-3xl font-bold text-white hover:underline mt-1 block tracking-tight">
                                    (044) 913-0248
                                </a>
                                <span class="text-xs text-white/70 block mt-2">Mobile SMS Hotline: <strong class="text-white">+63 917 842 6722</strong></span>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-white/15">
                            <a href="tel:0449130248" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-3 text-sm font-semibold text-osca-primary hover:bg-osca-muted transition-colors shadow-xs">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                Call OSCA Helpdesk Now
                            </a>
                        </div>
                    </div>

                    <!-- Right Vertical Emergency Numbers List -->
                    <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Hotline Item 1 -->
                        <div class="bg-white rounded-lg border border-osca-border p-5 flex flex-col justify-between shadow-xs">
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="text-xs font-semibold text-osca-primary uppercase tracking-wider">Disaster &amp; Rescue</span>
                                    <h4 class="text-base font-bold text-osca-ink mt-0.5">MDRRMO Santa Maria</h4>
                                </div>
                                <span class="size-8 rounded-full bg-osca-muted flex items-center justify-center text-osca-body">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                </span>
                            </div>
                            <p class="text-xs text-osca-body mt-2">24/7 emergency response, ambulance transport, and medical evacuation.</p>
                            <div class="mt-4 pt-3 border-t border-osca-border flex items-center justify-between">
                                <span class="text-lg font-bold text-osca-ink">(044) 913-1191</span>
                                <a href="tel:0449131191" class="text-xs font-semibold text-osca-primary hover:underline">Call</a>
                            </div>
                        </div>

                        <!-- Hotline Item 2 -->
                        <div class="bg-white rounded-lg border border-osca-border p-5 flex flex-col justify-between shadow-xs">
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="text-xs font-semibold text-osca-primary uppercase tracking-wider">Police Assistance</span>
                                    <h4 class="text-base font-bold text-osca-ink mt-0.5">Santa Maria PNP Station</h4>
                                </div>
                                <span class="size-8 rounded-full bg-osca-muted flex items-center justify-center text-osca-body">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                </span>
                            </div>
                            <p class="text-xs text-osca-body mt-2">24/7 public safety, law enforcement, and senior protection.</p>
                            <div class="mt-4 pt-3 border-t border-osca-border flex items-center justify-between">
                                <span class="text-lg font-bold text-osca-ink">(044) 913-2222</span>
                                <a href="tel:0449132222" class="text-xs font-semibold text-osca-primary hover:underline">Call</a>
                            </div>
                        </div>

                        <!-- Hotline Item 3 -->
                        <div class="bg-white rounded-lg border border-osca-border p-5 flex flex-col justify-between shadow-xs">
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="text-xs font-semibold text-osca-primary uppercase tracking-wider">Health Office</span>
                                    <h4 class="text-base font-bold text-osca-ink mt-0.5">Municipal Health (RHU)</h4>
                                </div>
                                <span class="size-8 rounded-full bg-osca-muted flex items-center justify-center text-osca-body">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                                </span>
                            </div>
                            <p class="text-xs text-osca-body mt-2">Healthcare consultations, prescription verification, and senior clinics.</p>
                            <div class="mt-4 pt-3 border-t border-osca-border flex items-center justify-between">
                                <span class="text-lg font-bold text-osca-ink">(044) 913-3333</span>
                                <a href="tel:0449133333" class="text-xs font-semibold text-osca-primary hover:underline">Call</a>
                            </div>
                        </div>

                        <!-- Hotline Item 4 -->
                        <div class="bg-white rounded-lg border border-osca-border p-5 flex flex-col justify-between shadow-xs">
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="text-xs font-semibold text-osca-primary uppercase tracking-wider">Fire Protection</span>
                                    <h4 class="text-base font-bold text-osca-ink mt-0.5">BFP Santa Maria</h4>
                                </div>
                                <span class="size-8 rounded-full bg-osca-muted flex items-center justify-center text-osca-body">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
                                </span>
                            </div>
                            <p class="text-xs text-osca-body mt-2">Fire emergency, residential hazard inspections, and rescue services.</p>
                            <div class="mt-4 pt-3 border-t border-osca-border flex items-center justify-between">
                                <span class="text-lg font-bold text-osca-ink">(044) 913-4444</span>
                                <a href="tel:0449134444" class="text-xs font-semibold text-osca-primary hover:underline">Call</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- DARK LEADERSHIP BAND -->
        <section class="bg-osca-ink text-white py-16 sm:py-20" aria-label="Leadership and Mandate">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <!-- Left: Mandate and Focus areas -->
                    <div class="lg:col-span-7 space-y-6">
                        <div class="inline-flex items-center gap-2 text-[13px] font-medium text-white/70">
                            <span class="size-2 rounded-full bg-white/60"></span>
                            <span>Leadership &amp; Mandate</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                            Dedicated to the Dignity and Welfare of Our Elders
                        </h2>
                        <p class="text-[15px] sm:text-base text-white/80 leading-relaxed">
                            Under Republic Act No. 7432 and Republic Act No. 9994 (Expanded Senior Citizens Act), the Office of the Senior Citizens Affairs of Santa Maria works to advocate for the rights, privileges, and overall welfare of our elderly. eLINGAP modernizes this mandate through transparent records and automated outreach.
                        </p>

                        <!-- Focus areas (priorities) in 2 columns -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3">
                            <div class="flex items-start gap-3">
                                <span class="size-5 rounded-full bg-white/10 text-white flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="text-sm font-medium text-white/90">Health &amp; Wellness Programs</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="size-5 rounded-full bg-white/10 text-white flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="text-sm font-medium text-white/90">Direct Benefit &amp; Pension Access</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="size-5 rounded-full bg-white/10 text-white flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="text-sm font-medium text-white/90">Community &amp; Barangay Engagement</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="size-5 rounded-full bg-white/10 text-white flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="text-sm font-medium text-white/90">Full Records Digitization</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: OSCA Head / Officer Portrait representation -->
                    <div class="lg:col-span-5">
                        <div class="rounded-lg border border-white/15 bg-white/5 p-6 sm:p-8 backdrop-blur-xs">
                            <div class="size-16 rounded-full bg-white/15 border border-white/20 flex items-center justify-center text-white text-xl font-bold mb-5">
                                OSCA
                            </div>
                            <blockquote class="text-base sm:text-lg text-white/90 italic leading-relaxed">
                                &ldquo;Our senior citizens are the pillars of Santa Maria's heritage. Ensuring their well-being, dignity, and prompt access to public services is our highest duty.&rdquo;
                            </blockquote>
                            <div class="mt-6 pt-4 border-t border-white/15">
                                <p class="font-bold text-white text-base">Office of the Senior Citizens Affairs</p>
                                <p class="text-xs text-white/70">Municipal Government of Santa Maria, Bulacan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- STATS STRIP -->
        <section class="py-14 sm:py-16 bg-white border-b border-osca-border" aria-label="System Metrics">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-left">
                    <div>
                        <p class="text-3xl sm:text-4xl lg:text-5xl font-bold text-osca-primary tracking-tight">18,400+</p>
                        <p class="text-sm font-medium text-osca-body mt-2">Registered Senior Citizens</p>
                    </div>
                    <div>
                        <p class="text-3xl sm:text-4xl lg:text-5xl font-bold text-osca-primary tracking-tight">24</p>
                        <p class="text-sm font-medium text-osca-body mt-2">Barangays Covered</p>
                    </div>
                    <div>
                        <p class="text-3xl sm:text-4xl lg:text-5xl font-bold text-osca-primary tracking-tight">98.6%</p>
                        <p class="text-sm font-medium text-osca-body mt-2">SMS Delivery Rate</p>
                    </div>
                    <div>
                        <p class="text-3xl sm:text-4xl lg:text-5xl font-bold text-osca-primary tracking-tight">48,000+</p>
                        <p class="text-sm font-medium text-osca-body mt-2">Benefit Claims Disbursed</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ANNOUNCEMENTS / WEATHER & HOTLINE SUMMARY WIDGET -->
        <section id="announcements" class="py-16 sm:py-20 bg-osca-muted">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Left: Announcements & Schedules -->
                    <div class="lg:col-span-7 space-y-4">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <span class="text-[13px] font-medium text-osca-primary">Community Updates</span>
                                <h3 class="text-xl font-bold text-osca-ink mt-0.5">Upcoming Distribution Schedules</h3>
                            </div>
                            <span class="text-xs font-semibold text-osca-primary">2026 Calendar</span>
                        </div>

                        <!-- Schedule Card 1 -->
                        <div class="rounded-lg border border-osca-border bg-white p-5 shadow-xs flex items-start gap-4">
                            <div class="size-14 rounded-lg bg-osca-primary/10 text-osca-primary flex flex-col items-center justify-center shrink-0 font-bold leading-tight">
                                <span class="text-xs uppercase">OCT</span>
                                <span class="text-lg leading-none">12</span>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="rounded bg-osca-success/15 text-osca-success px-2 py-0.5 text-[11px] font-semibold">Social Pension</span>
                                    <span class="text-xs text-osca-body">8:00 AM – 3:00 PM</span>
                                </div>
                                <h4 class="text-base font-semibold text-osca-ink mt-1">3rd Quarter Social Pension &amp; Cash Gift Payout</h4>
                                <p class="text-xs text-osca-body mt-1">Santa Maria Municipal Gymnasium for Barangays Poblacion, Bagbaguin, and Cay Pombo.</p>
                            </div>
                        </div>

                        <!-- Schedule Card 2 -->
                        <div class="rounded-lg border border-osca-border bg-white p-5 shadow-xs flex items-start gap-4">
                            <div class="size-14 rounded-lg bg-osca-primary/10 text-osca-primary flex flex-col items-center justify-center shrink-0 font-bold leading-tight">
                                <span class="text-xs uppercase">OCT</span>
                                <span class="text-lg leading-none">20</span>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="rounded bg-osca-warning/20 text-osca-ink px-2 py-0.5 text-[11px] font-semibold">Medical Mission</span>
                                    <span class="text-xs text-osca-body">9:00 AM – 2:00 PM</span>
                                </div>
                                <h4 class="text-base font-semibold text-osca-ink mt-1">Free Cataract Screening &amp; Senior Eye Care</h4>
                                <p class="text-xs text-osca-body mt-1">Municipal Health Office (RHU) in coordination with Bulacan Provincial Health.</p>
                            </div>
                        </div>

                        <!-- Schedule Card 3 -->
                        <div class="rounded-lg border border-osca-border bg-white p-5 shadow-xs flex items-start gap-4">
                            <div class="size-14 rounded-lg bg-osca-primary/10 text-osca-primary flex flex-col items-center justify-center shrink-0 font-bold leading-tight">
                                <span class="text-xs uppercase">OCT</span>
                                <span class="text-lg leading-none">28</span>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="rounded bg-osca-primary/15 text-osca-primary px-2 py-0.5 text-[11px] font-semibold">Assembly</span>
                                    <span class="text-xs text-osca-body">1:30 PM – 4:00 PM</span>
                                </div>
                                <h4 class="text-base font-semibold text-osca-ink mt-1">Quarterly Barangay Senior Citizen Chapter Assembly</h4>
                                <p class="text-xs text-osca-body mt-1">Updates on RA 9994 local compliance and welfare programs.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Weather Widget & Hotline Summary -->
                    <div class="lg:col-span-5 space-y-4">
                        <div class="rounded-lg border border-osca-border bg-white p-6 shadow-xs">
                            <div class="flex items-center justify-between border-b border-osca-border pb-4">
                                <div>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-osca-primary">Local Conditions</span>
                                    <h4 class="text-base font-bold text-osca-ink">Santa Maria, Bulacan</h4>
                                </div>
                                <span class="text-xs text-osca-body">Today</span>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="size-12 rounded-full bg-osca-warning/15 text-osca-warning flex items-center justify-center">
                                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                                    </span>
                                    <div>
                                        <p class="text-3xl font-bold text-osca-ink leading-none">29°C</p>
                                        <p class="text-xs text-osca-body mt-1">Partly Cloudy · Fair Weather</p>
                                    </div>
                                </div>
                                <div class="text-right text-xs text-osca-body space-y-1">
                                    <p>Humidity: <strong>72%</strong></p>
                                    <p>Air Quality: <strong>Good</strong></p>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-osca-border text-xs text-osca-body flex items-center gap-2">
                                <span class="size-2 rounded-full bg-osca-success"></span>
                                <span>Ideal outdoor conditions for senior citizen counter visits.</span>
                            </div>
                        </div>

                        <!-- Hotline Quick Summary -->
                        <div class="rounded-lg border border-osca-border bg-white p-6 shadow-xs">
                            <h4 class="text-sm font-bold text-osca-ink">OSCA Express Service Summary</h4>
                            <p class="text-xs text-osca-body mt-1">All service counters are operating today at the Santa Maria Municipal Hall Complex.</p>
                            <div class="mt-4 space-y-2 text-xs">
                                <div class="flex justify-between py-1 border-b border-osca-border">
                                    <span class="text-osca-body">Office Hours:</span>
                                    <span class="font-semibold text-osca-ink">Mon – Fri, 8:00 AM – 5:00 PM</span>
                                </div>
                                <div class="flex justify-between py-1 border-b border-osca-border">
                                    <span class="text-osca-body">Senior Express Lane:</span>
                                    <span class="font-semibold text-osca-success">Window 1 &amp; Window 2</span>
                                </div>
                                <div class="flex justify-between py-1">
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

    <!-- FOOTER (near-black background) -->
    <footer class="bg-osca-ink text-white/80 border-t border-white/10 text-sm" aria-label="Page footer">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10">
                <!-- Brand Info -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-lg bg-osca-primary text-white font-bold text-lg">
                            eL
                        </span>
                        <div>
                            <span class="text-xl font-bold tracking-tight text-white block">eLINGAP</span>
                            <span class="text-xs text-white/70 block">Office of the Senior Citizens Affairs</span>
                        </div>
                    </div>
                    <p class="text-xs sm:text-sm text-white/70 leading-relaxed max-w-sm">
                        An integrated web-based records management and automated SMS notification system serving the senior citizens of Santa Maria, Bulacan.
                    </p>
                    <p class="text-xs text-white/60">
                        Republic of the Philippines · Province of Bulacan · Municipality of Santa Maria
                    </p>
                </div>

                <!-- Quick Links -->
                <div class="lg:col-span-3 space-y-3">
                    <h5 class="text-sm font-semibold uppercase tracking-wider text-white">Quick Navigation</h5>
                    <ul class="space-y-2 text-xs sm:text-sm text-white/70">
                        <li><a href="#hero" class="hover:text-white transition-colors">Home</a></li>
                        <li><a href="#programs" class="hover:text-white transition-colors">Programs &amp; Benefits</a></li>
                        <li><a href="#records" class="hover:text-white transition-colors">Records Management</a></li>
                        <li><a href="#sms-notifications" class="hover:text-white transition-colors">SMS Notifications</a></li>
                        <li><a href="#about" class="hover:text-white transition-colors">About OSCA Santa Maria</a></li>
                        <li><a href="#emergency" class="hover:text-white transition-colors">Emergency Hotlines</a></li>
                    </ul>
                </div>

                <!-- Office Contact & Hours -->
                <div class="lg:col-span-4 space-y-3">
                    <h5 class="text-sm font-semibold uppercase tracking-wider text-white">Office of Senior Citizens Affairs</h5>
                    <div class="space-y-2 text-xs sm:text-sm text-white/70">
                        <p class="flex items-start gap-2">
                            <svg class="size-4 shrink-0 mt-0.5 text-white/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>Ground Floor, Municipal Hall Building, Poblacion, Santa Maria, Bulacan 3022</span>
                        </p>
                        <p class="flex items-center gap-2">
                            <svg class="size-4 shrink-0 text-white/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            <span>osca@santamariabulacan.gov.ph</span>
                        </p>
                        <p class="flex items-center gap-2">
                            <svg class="size-4 shrink-0 text-white/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <span>(044) 913-0248</span>
                        </p>
                        <p class="text-xs text-white/50 pt-2 border-t border-white/10">
                            Counter Hours: Monday to Friday · 8:00 AM to 5:00 PM (Excluding Official Holidays)
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bottom Sub-bar -->
            <div class="mt-12 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-white/60">
                <p>&copy; {{ date('Y') }} Office of the Senior Citizens Affairs (OSCA) — Santa Maria, Bulacan. All rights reserved.</p>
                <div class="flex items-center gap-4">
                    <span>Data Privacy Act of 2012 Compliant</span>
                    <span>·</span>
                    <a href="#main-content" class="text-white hover:underline">Back to top &uarr;</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- MODAL: File a Concern -->
    <div id="concern-modal" class="fixed inset-0 z-50 hidden bg-osca-ink/70 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="concern-title">
        <div class="bg-white rounded-lg border border-osca-border max-w-lg w-full p-6 sm:p-7 shadow-xl">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-osca-primary">Citizen Assistance</span>
                    <h3 id="concern-title" class="text-xl font-bold text-osca-ink mt-0.5">File a Senior Citizen Concern</h3>
                    <p class="text-xs text-osca-body mt-1">Submit inquiries or requests directly to OSCA Santa Maria.</p>
                </div>
                <button type="button" id="concern-close-btn" class="size-8 rounded-md text-osca-body hover:bg-osca-muted hover:text-osca-ink flex items-center justify-center" aria-label="Close concern modal">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <form action="#submitted" onsubmit="event.preventDefault(); alert('Your concern has been submitted to the OSCA Santa Maria Helpdesk. We will contact you shortly.'); document.getElementById('concern-modal').classList.add('hidden');" class="mt-5 space-y-4 text-left">
                <div>
                    <label for="concern-name" class="block text-xs font-semibold text-osca-ink mb-1">Full Name of Senior Citizen / Caregiver</label>
                    <input type="text" id="concern-name" required class="w-full rounded-md border border-osca-border px-3.5 py-2 text-sm text-osca-ink focus:border-osca-primary focus:outline-hidden" placeholder="e.g. Juanita Dela Cruz">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="concern-contact" class="block text-xs font-semibold text-osca-ink mb-1">Mobile Contact Number</label>
                        <input type="tel" id="concern-contact" required class="w-full rounded-md border border-osca-border px-3.5 py-2 text-sm text-osca-ink focus:border-osca-primary focus:outline-hidden" placeholder="0917XXXXXXX">
                    </div>
                    <div>
                        <label for="concern-barangay" class="block text-xs font-semibold text-osca-ink mb-1">Barangay</label>
                        <select id="concern-barangay" required class="w-full rounded-md border border-osca-border px-3.5 py-2 text-sm text-osca-ink focus:border-osca-primary focus:outline-hidden">
                            <option value="">Select Barangay</option>
                            <option value="Bagbaguin">Bagbaguin</option>
                            <option value="Balasing">Balasing</option>
                            <option value="Buenavista">Buenavista</option>
                            <option value="Bulac">Bulac</option>
                            <option value="Camangyanan">Camangyanan</option>
                            <option value="Catmon">Catmon</option>
                            <option value="Cay Pombo">Cay Pombo</option>
                            <option value="Caysio">Caysio</option>
                            <option value="Guyong">Guyong</option>
                            <option value="Lalangan">Lalangan</option>
                            <option value="Mag-asawang Sapa">Mag-asawang Sapa</option>
                            <option value="Mahabang Parang">Mahabang Parang</option>
                            <option value="Manggahan">Manggahan</option>
                            <option value="Parada">Parada</option>
                            <option value="Poblacion">Poblacion</option>
                            <option value="Pulong Buhangin">Pulong Buhangin</option>
                            <option value="San Gabriel">San Gabriel</option>
                            <option value="San Jose Patag">San Jose Patag</option>
                            <option value="San Vicente">San Vicente</option>
                            <option value="Santa Clara">Santa Clara</option>
                            <option value="Santa Cruz">Santa Cruz</option>
                            <option value="Silangan">Silangan</option>
                            <option value="Tabing Bakod">Tabing Bakod</option>
                            <option value="Tumana">Tumana</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="concern-type" class="block text-xs font-semibold text-osca-ink mb-1">Nature of Concern</label>
                    <select id="concern-type" required class="w-full rounded-md border border-osca-border px-3.5 py-2 text-sm text-osca-ink focus:border-osca-primary focus:outline-hidden">
                        <option value="Pension Inquiry">Social Pension Inquiry</option>
                        <option value="Senior ID Application">Senior Citizen ID Application / Renewal</option>
                        <option value="Burial Assistance">Burial / Medical Financial Grant</option>
                        <option value="SMS Notification Update">Update Mobile Number for SMS</option>
                        <option value="General Inquiry">General Welfare Concern</option>
                    </select>
                </div>
                <div>
                    <label for="concern-details" class="block text-xs font-semibold text-osca-ink mb-1">Details of Concern</label>
                    <textarea id="concern-details" rows="3" required class="w-full rounded-md border border-osca-border px-3.5 py-2 text-sm text-osca-ink focus:border-osca-primary focus:outline-hidden" placeholder="Please describe your concern or assistance needed..."></textarea>
                </div>
                <div class="pt-2 flex items-center justify-end gap-3">
                    <button type="button" id="concern-cancel-btn" class="px-4 py-2 text-sm font-medium text-osca-body hover:text-osca-ink">Cancel</button>
                    <button type="submit" class="rounded-lg bg-osca-primary px-5 py-2 text-sm font-semibold text-white hover:bg-osca-primary-dark transition-colors">Submit Concern</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Quick Search -->
    <div id="search-modal" class="fixed inset-0 z-50 hidden bg-osca-ink/70 backdrop-blur-xs flex items-start justify-center p-4 pt-20" role="dialog" aria-modal="true" aria-labelledby="search-modal-title">
        <div class="bg-white rounded-lg border border-osca-border max-w-lg w-full p-5 shadow-xl">
            <div class="flex items-center justify-between pb-3 border-b border-osca-border">
                <h3 id="search-modal-title" class="text-sm font-bold text-osca-ink">Search eLINGAP Portal</h3>
                <button type="button" id="search-close-btn" class="size-7 rounded text-osca-body hover:bg-osca-muted flex items-center justify-center" aria-label="Close search">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="mt-4">
                <div class="relative">
                    <input type="search" id="quick-search-input" class="w-full rounded-lg border border-osca-border pl-10 pr-4 py-2.5 text-sm text-osca-ink focus:border-osca-primary focus:outline-hidden" placeholder="Search for programs, hotlines, benefits...">
                    <svg class="size-4 absolute left-3.5 top-3.5 text-osca-body" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-osca-border">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-osca-body block mb-2">Quick Navigation</span>
                <div class="flex flex-wrap gap-2 text-xs">
                    <a href="#programs" class="rounded-md bg-osca-muted px-2.5 py-1 text-osca-ink hover:bg-osca-primary hover:text-white transition-colors">Social Pension</a>
                    <a href="#records" class="rounded-md bg-osca-muted px-2.5 py-1 text-osca-ink hover:bg-osca-primary hover:text-white transition-colors">Senior ID Registration</a>
                    <a href="#emergency" class="rounded-md bg-osca-muted px-2.5 py-1 text-osca-ink hover:bg-osca-primary hover:text-white transition-colors">Emergency Hotlines</a>
                    <a href="#announcements" class="rounded-md bg-osca-muted px-2.5 py-1 text-osca-ink hover:bg-osca-primary hover:text-white transition-colors">Payout Schedules</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Client Script for snappy UI interactions without external dependencies -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Mobile navigation menu toggle
            const mobileBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            const iconBars = document.getElementById('menu-icon-bars');
            const iconClose = document.getElementById('menu-icon-close');

            if (mobileBtn && mobileMenu) {
                mobileBtn.addEventListener('click', () => {
                    const isOpen = !mobileMenu.classList.contains('hidden');
                    mobileMenu.classList.toggle('hidden');
                    iconBars.classList.toggle('hidden', !isOpen);
                    iconClose.classList.toggle('hidden', isOpen);
                    mobileBtn.setAttribute('aria-expanded', !isOpen);
                });

                // Auto-close menu when clicking mobile links
                mobileMenu.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', () => {
                        mobileMenu.classList.add('hidden');
                        iconBars.classList.remove('hidden');
                        iconClose.classList.add('hidden');
                        mobileBtn.setAttribute('aria-expanded', 'false');
                    });
                });
            }

            // File Concern Modal interactions
            const concernModal = document.getElementById('concern-modal');
            const concernOpenBtn = document.getElementById('concern-open-btn');
            const mobileConcernBtn = document.getElementById('mobile-concern-btn');
            const concernCloseBtn = document.getElementById('concern-close-btn');
            const concernCancelBtn = document.getElementById('concern-cancel-btn');

            const toggleConcernModal = (show) => {
                if (concernModal) {
                    concernModal.classList.toggle('hidden', !show);
                    if (show) {
                        const nameInput = document.getElementById('concern-name');
                        if (nameInput) nameInput.focus();
                    }
                }
            };

            if (concernOpenBtn) concernOpenBtn.addEventListener('click', () => toggleConcernModal(true));
            if (mobileConcernBtn) {
                mobileConcernBtn.addEventListener('click', () => {
                    if (mobileMenu) mobileMenu.classList.add('hidden');
                    toggleConcernModal(true);
                });
            }
            if (concernCloseBtn) concernCloseBtn.addEventListener('click', () => toggleConcernModal(false));
            if (concernCancelBtn) concernCancelBtn.addEventListener('click', () => toggleConcernModal(false));

            // Search Modal interactions
            const searchModal = document.getElementById('search-modal');
            const searchOpenBtn = document.getElementById('search-open-btn');
            const searchCloseBtn = document.getElementById('search-close-btn');
            const searchInput = document.getElementById('quick-search-input');

            const toggleSearchModal = (show) => {
                if (searchModal) {
                    searchModal.classList.toggle('hidden', !show);
                    if (show && searchInput) {
                        setTimeout(() => searchInput.focus(), 50);
                    }
                }
            };

            if (searchOpenBtn) searchOpenBtn.addEventListener('click', () => toggleSearchModal(true));
            if (searchCloseBtn) searchCloseBtn.addEventListener('click', () => toggleSearchModal(false));

            // Close modals when clicking backdrop or pressing Esc
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    toggleConcernModal(false);
                    toggleSearchModal(false);
                }
            });

            [concernModal, searchModal].forEach(modal => {
                if (modal) {
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) {
                            modal.classList.add('hidden');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
