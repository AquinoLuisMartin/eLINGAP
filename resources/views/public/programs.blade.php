<section id="programs" class="scroll-mt-24 border-t border-slate-200 bg-slate-50 py-14 sm:py-20" aria-labelledby="public-programs-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 max-w-3xl sm:mb-10">
            <p class="text-xs font-semibold tracking-[0.15em] text-osca-primary uppercase">Welfare &amp; assistance</p>
            <h2 id="public-programs-title" class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">Support for every stage of life.</h2>
            <p class="mt-3 text-sm leading-relaxed text-osca-body sm:text-base">Current active programs recorded by the office. Individual eligibility and assistance are confirmed during assessment.</p>
        </div>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
            @forelse ($publicPrograms as $program)
                <article class="flex flex-col justify-between rounded-xl border border-osca-border bg-white p-5 sm:p-6">
                    <div>
                        <p class="text-xs font-medium text-osca-primary">{{ $program->agency }}</p>
                        <h3 class="mt-2 text-base font-semibold text-osca-ink sm:text-lg">{{ $program->name }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-osca-body">{{ $program->description ?: 'Contact OSCA for program requirements.' }}</p>
                    </div>
                    <p class="mt-5 border-t border-osca-border pt-4 text-sm text-osca-body">
                        @if ($program->default_amount !== null)
                            Default assistance: <span class="font-semibold text-osca-ink">{{ \App\Support\PhilippineCurrency::format($program->default_amount) }}</span>
                        @else
                            Amount confirmed during assessment.
                        @endif
                    </p>
                </article>
            @empty
                <div class="flex flex-col items-start gap-4 rounded-xl border border-slate-200 bg-white p-6 sm:col-span-2 sm:flex-row sm:gap-5 sm:p-8 lg:col-span-3">
                    <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-osca-primary" aria-hidden="true"><x-admin.icon name="heart-pulse" :size="24" /></span>
                    <div><h3 class="text-base font-semibold text-slate-900">Looking for program information?</h3><p class="mt-2 text-sm leading-relaxed text-slate-600">No active programs are currently published. Contact the OSCA office for current program information.</p><a href="#office-contact" class="mt-3 inline-flex min-h-11 items-center gap-2 rounded text-sm font-semibold text-osca-primary hover:underline">Ask the OSCA office <span aria-hidden="true">&rarr;</span></a></div>
                </div>
            @endforelse
        </div>
    </div>
</section>
