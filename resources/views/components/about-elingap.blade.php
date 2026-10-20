<section id="about-view" class="hidden min-h-[65vh] bg-slate-50 text-slate-900" aria-labelledby="about-elingap-title">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
        <div class="max-w-4xl">
            <img src="{{ asset('images/eLINGAP.png') }}" alt="eLINGAP logo" class="mb-5 size-24 object-contain" width="96" height="96" loading="lazy">
            <h1 id="about-elingap-title" class="text-slate-900 text-3xl sm:text-4xl font-semibold tracking-tight">About eLINGAP</h1>
            <p class="mt-5 text-slate-600 text-sm sm:text-base leading-relaxed max-w-3xl">
                eLINGAP supports the Office of the Senior Citizens Affairs (OSCA) in Santa Maria, Bulacan with organized senior citizen records, benefit coordination, and SMS notifications. It helps authorized staff review information and keep seniors and their recorded representatives informed.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-10">
            <article class="bg-white rounded-xl border border-slate-200 p-6">
                <h2 class="text-slate-900 font-semibold text-lg">Unified Masterlist</h2>
                <p class="mt-2 text-sm text-slate-600 leading-relaxed">A centralized registry connecting senior citizen records across participating barangays.</p>
            </article>
            <article class="bg-white rounded-xl border border-slate-200 p-6">
                <h2 class="text-slate-900 font-semibold text-lg">Automated SMS Broadcasts</h2>
                <p class="mt-2 text-sm text-slate-600 leading-relaxed">Office announcements and confirmed payout details sent to registered mobile numbers.</p>
            </article>
            <article class="bg-white rounded-xl border border-slate-200 p-6">
                <h2 class="text-slate-900 font-semibold text-lg">Paperless Verification</h2>
                <p class="mt-2 text-sm text-slate-600 leading-relaxed">Staff review of registration documents, ID issuance, and benefit eligibility in a shared workspace.</p>
            </article>
        </div>

        <button type="button" data-view="home" class="mt-8 inline-flex min-h-11 cursor-pointer items-center gap-3 rounded-lg bg-osca-primary px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-osca-primary-dark"><span aria-hidden="true">&larr;</span>Back to home</button>
    </div>
</section>
