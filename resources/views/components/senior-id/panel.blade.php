@props(['title', 'description' => null])
<section {{ $attributes->class(['min-w-0 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900 sm:p-6']) }}>
    <h3 class="text-lg font-medium text-osca-primary dark:text-slate-100">{{ $title }}</h3>
    @if ($description)<p class="mt-1 max-w-prose text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $description }}</p>@endif
    @if ($slot->isNotEmpty())<div class="mt-5">{{ $slot }}</div>@endif
</section>
