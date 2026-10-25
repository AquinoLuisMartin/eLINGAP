<section id="programs" class="scroll-mt-24 bg-white py-14 sm:py-20" aria-labelledby="public-programs-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 max-w-3xl sm:mb-10">
            <h2 id="public-programs-title" class="text-3xl font-semibold tracking-tight text-osca-ink sm:text-4xl">Programs and welfare benefits</h2>
        </div>
        <ol class="grid auto-rows-fr gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="Program directory">
            @forelse ($publicPrograms as $program)
                <li class="public-card flex min-w-0 items-center px-5 py-5"><h3 class="text-base leading-relaxed font-semibold break-words text-osca-ink">{{ $program->name }}</h3></li>
            @empty
                <li class="public-card px-5 py-4 text-sm text-slate-600 sm:col-span-2 lg:col-span-3">No programs are currently published.</li>
            @endforelse
        </ol>
    </div>
</section>
