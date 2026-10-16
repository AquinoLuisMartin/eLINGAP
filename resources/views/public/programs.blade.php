<section id="programs" class="scroll-mt-20 border-t border-osca-border bg-osca-muted/60 py-14 sm:py-20" aria-labelledby="public-programs-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-10 max-w-3xl sm:mb-12">
            <h2 id="public-programs-title" class="text-2xl font-semibold tracking-tight text-osca-ink sm:text-3xl lg:text-4xl">Programs &amp; Welfare <span class="text-osca-primary">Benefits</span></h2>
            <p class="mt-3 text-sm leading-relaxed text-osca-body sm:text-base">Current active programs recorded by the office. Individual eligibility and assistance are confirmed during assessment.</p>
        </div>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
            @forelse ($publicPrograms as $program)
                <article class="flex flex-col justify-between rounded-xl border border-osca-border bg-white p-5 sm:p-6">
                    <div>
                        <p class="text-xs font-medium text-osca-primary">{{ $program->agency }}</p>
                        <h3 class="mt-2 text-base font-semibold text-osca-ink sm:text-lg">{{ $program->name }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-osca-body">{{ $program->description ?: 'Contact OSCA for program requirements.' }}</p>
                    </div>
                    <p class="mt-5 border-t border-osca-border pt-3 text-sm text-osca-body">
                        @if ($program->default_amount !== null)
                            Default assistance: <span class="font-semibold text-osca-ink">{{ \App\Support\PhilippineCurrency::format($program->default_amount) }}</span>
                        @else
                            Amount confirmed during assessment.
                        @endif
                    </p>
                </article>
            @empty
                <p class="rounded-xl border border-osca-border bg-white p-5 text-sm text-osca-body sm:col-span-2 lg:col-span-4">No active programs are currently published. Contact the OSCA office for current program information.</p>
            @endforelse
        </div>
    </div>
</section>
