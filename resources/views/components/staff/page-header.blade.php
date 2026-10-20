@props(['title', 'description' => null])

<section {{ $attributes->class(['staff-page-heading mb-5 flex flex-col justify-between gap-4 sm:flex-row sm:items-start']) }}>
    <div>
        <h2 class="text-2xl font-semibold tracking-tight">{{ $title }}</h2>
        @if ($description)<p class="mt-2 text-sm text-[var(--muted)]">{{ $description }}</p>@endif
    </div>
    @if ($slot->isNotEmpty())<div class="staff-page-actions flex flex-wrap items-center gap-3">{{ $slot }}</div>@endif
</section>
